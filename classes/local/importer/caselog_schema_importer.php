<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_competvet\local\importer;

use mod_competvet\local\persistent\case_version;
use mod_competvet\local\persistent\case_cat;
use mod_competvet\local\persistent\case_field;

/**
 * Import Caselog form schema from JSON.
 *
 * Idempotent: creates records only if they don't already exist.
 * Safe to run multiple times.
 *
 * @package    mod_competvet
 * @copyright  2026 CALL Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class caselog_schema_importer {
    /**
     * Import schema from JSON file.
     *
     * @param string $filepath Path to the JSON schema file.
     * @param bool   $verbose  Whether to collect log messages.
     * @return array Log messages (empty if not verbose).
     */
    public static function import(string $filepath, bool $verbose = false): array {
        global $DB;
        $log = [];

        if (!is_readable($filepath)) {
            throw new \moodle_exception('File not found: ' . $filepath);
        }

        $json = file_get_contents($filepath);
        if ($json === false) {
            throw new \moodle_exception('Failed to read file: ' . $filepath);
        }

        $schema = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception('Invalid JSON: ' . json_last_error_msg());
        }

        if (!isset($schema['versions']) || !isset($schema['categories'])) {
            throw new \moodle_exception('Schema must contain versions and categories keys.');
        }

        // Validate versions array structure.
        foreach ($schema['versions'] as $versiondata) {
            if (!isset($versiondata['key']) || !isset($versiondata['name'])) {
                throw new \moodle_exception('Each version must have a key and a name.');
            }
        }

        // Check for duplicate version keys.
        $seenkeys = [];
        foreach ($schema['versions'] as $versiondata) {
            if (isset($seenkeys[$versiondata['key']])) {
                throw new \moodle_exception('Duplicate version key found: ' . $versiondata['key']);
            }
            $seenkeys[$versiondata['key']] = true;
        }

        // Validate categories array structure.
        foreach ($schema['categories'] as $categorydata) {
            if (!isset($categorydata['name']) || !isset($categorydata['versionkey'])) {
                throw new \moodle_exception('Each category must have a name and a versionkey.');
            }
        }

        // Validate fields array structure.
        foreach ($schema['categories'] as $categorydata) {
            if (!empty($categorydata['fields']) && is_array($categorydata['fields'])) {
                foreach ($categorydata['fields'] as $fielddata) {
                    if (!isset($fielddata['idnumber']) || !isset($fielddata['name']) || !isset($fielddata['type'])) {
                        throw new \moodle_exception('Each field must have idnumber, name, and type.');
                    }
                    if (!in_array($fielddata['type'], case_field::FIELD_TYPES, true)) {
                        throw new \moodle_exception(sprintf(
                            'Invalid field type \'%s\' for field \'%s\' in category \'%s\'. Allowed types: %s.',
                            $fielddata['type'],
                            $fielddata['idnumber'],
                            $categorydata['name'],
                            implode(', ', case_field::FIELD_TYPES)
                        ));
                    }
                }
            }
        }

        // Start transaction.
        $transaction = $DB->start_delegated_transaction();

        try {
            // 1. Create versions.
            // Map schema keys to database IDs. Database IDs must not be stored in the schema.
            $versionids = [];
            $versionnames = [];
            foreach ($schema['versions'] as $versiondata) {
                $existing = self::find_version($versiondata['key'], $versiondata['name']);
                if ($existing) {
                    $metadata = $existing->read_metadata();
                    $metadata['schemaid'] = $versiondata['key'];
                    $existing->set('metadata', json_encode($metadata, JSON_UNESCAPED_UNICODE));
                    $existing->set('name', $versiondata['name']);
                    if ($verbose) {
                        $log[] = "Version '{$versiondata['name']}' already exists (id: {$existing->get('id')}).";
                    }
                    $versionids[$versiondata['key']] = $existing->get('id');
                    $versionnames[$versiondata['key']] = $versiondata['name'];
                    continue;
                }

                $metadata = $versiondata['metadata'] ?? [];
                $metadata['schemaid'] = $versiondata['key'];
                $version = new case_version(0, (object) [
                    'name'     => $versiondata['name'],
                    'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE),
                ]);
                $version->create();
                $versionids[$versiondata['key']] = $version->get('id');
                $versionnames[$versiondata['key']] = $versiondata['name'];
                if ($verbose) {
                    $log[] = "Created version '{$versiondata['name']}' (id: {$version->get('id')}).";
                }
            }

            // 2. Create categories and fields per version.
            $categorycache = [];
            foreach ($schema['categories'] as $categorydata) {
                $schemaversionkey = $categorydata['versionkey'] ?? '';
                if (!isset($versionids[$schemaversionkey])) {
                    if ($verbose) {
                        $msg = "Warning: version key '{$schemaversionkey}' not found. Skipping '{$categorydata['name']}'.";
                        $log[] = $msg;
                    }
                    continue;
                }
                $versionid = $versionids[$schemaversionkey];
                $versionname = $versionnames[$schemaversionkey];

                $categorykey = $categorydata['idnumber'] ?? 'category' . substr(md5($categorydata['name']), 0, 12);
                $cachekey = $categorykey . '|' . $versionid;
                if (!isset($categorycache[$cachekey])) {
                    // Try to reuse a pre-versioned category for the legacy version.
                    $reusedid = self::try_reuse_legacy_category(
                        $versionid,
                        $categorydata['name'],
                        $versionids,
                        $versionnames,
                        $log,
                        $verbose
                    );
                    if ($reusedid !== null) {
                        $categorycache[$cachekey] = $reusedid;
                    } else {
                        $category = case_cat::get_record([
                        'idnumber' => $categorykey,
                        'versionid' => $versionid,
                        ]);
                        if (!$category) {
                            $category = case_cat::get_record([
                                'name' => $categorydata['name'],
                                'versionid' => $versionid,
                            ]);
                        }
                        if ($category) {
                            $category->set('idnumber', $categorykey);
                            $category->set('name', $categorydata['name']);
                            $category->set('description', $categorydata['description'] ?? '');
                            $category->set('sortorder', $categorydata['sortorder'] ?? $category->get('sortorder'));
                            $category->update();
                            if ($verbose) {
                                $msg = "Category '{$categorydata['name']}' exists in '{$versionname}' "
                                    . "(id: {$category->get('id')}).";
                                $log[] = $msg;
                            }
                            $categorycache[$cachekey] = $category->get('id');
                        } else {
                            $category = new case_cat(0, (object) [
                            'name'        => $categorydata['name'],
                            'idnumber'    => $categorykey,
                            'sortorder'   => case_cat::count_records() + 1,
                            'description' => $categorydata['description'] ?? '',
                            'versionid'   => $versionid,
                            ]);
                            $category->create();
                            $categorycache[$cachekey] = $category->get('id');
                            if ($verbose) {
                                $msg = "Created category '{$categorydata['name']}' in '{$versionname}' "
                                    . "(id: {$category->get('id')}).";
                                $log[] = $msg;
                            }
                        }
                    }
                }

                $categoryid = $categorycache[$cachekey];

                // Create fields.
                if (!empty($categorydata['fields'])) {
                    foreach ($categorydata['fields'] as $fielddata) {
                        $existingfield = case_field::get_record([
                            'idnumber' => $fielddata['idnumber'],
                            'categoryid' => $categoryid,
                        ]);
                        if ($existingfield) {
                            if (
                                $existingfield->get('type') !== $fielddata['type']
                                    && $DB->record_exists('competvet_case_data', ['fieldid' => $existingfield->get('id')])
                            ) {
                                throw new \moodle_exception(
                                    'Cannot change type of field with existing data: ' . $fielddata['idnumber']
                                );
                            }
                            $existingfield->set('name', $fielddata['name']);
                            $existingfield->set('type', $fielddata['type']);
                            $existingfield->set('description', $fielddata['description'] ?? '');
                            $existingfield->set('sortorder', $fielddata['sortorder'] ?? 0);
                            // Update configuration in place so flags such as removed can evolve safely.
                            $existingfield->set('configdata', $fielddata['configdata'] ?? null);
                            $existingfield->update();
                            if ($verbose) {
                                $msg = "Field '{$fielddata['idnumber']}' exists in '{$categorydata['name']}'.";
                                $log[] = $msg;
                            }
                            continue;
                        }

                        $field = new case_field(0, (object) [
                            'idnumber'    => $fielddata['idnumber'],
                            'name'        => $fielddata['name'],
                            'type'        => $fielddata['type'],
                            'description' => $fielddata['description'] ?? '',
                            'sortorder'   => $fielddata['sortorder'] ?? 0,
                            'categoryid'  => $categoryid,
                            'configdata'  => $fielddata['configdata'] ?? null,
                        ]);
                        $field->create();
                        if ($verbose) {
                            $log[] = "Created field '{$fielddata['idnumber']}' in category '{$categorydata['name']}'.";
                        }
                    }
                }
            }

            $transaction->allow_commit();
            \cache::make('mod_competvet', 'casestructures')->purge();
            if ($verbose) {
                $log[] = "Import completed successfully.";
            }
        } catch (\Exception $e) {
            $transaction->rollback($e);
            throw $e;
        }

        return $log;
    }

    /**
     * Find a version by its schema identity, falling back to its current name.
     *
     * @param string $schemaid Stable schema identifier.
     * @param string $name Display name.
     * @return case_version|null
     */
    private static function find_version(string $schemaid, string $name): ?case_version {
        foreach (case_version::get_records([]) as $version) {
            if (($version->read_metadata()['schemaid'] ?? null) === $schemaid || $version->get('name') === $name) {
                return $version;
            }
        }
        return null;
    }

    /**
     * Look up the legacy version ID by name rather than by key.
     *
     * This prevents a malicious or malformed JSON from triggering the legacy
     * migration path by injecting a version with key 'legacy'.
     *
     * @param array  $versionids    Map of version keys to DB IDs.
     * @param array  $versionnames  Map of version keys to version names.
     * @return int|null Legacy version DB ID, or null if not found.
     */
    private static function get_legacy_version_id(array $versionids, array $versionnames): ?int {
        foreach ($versionids as $key => $id) {
            if ($versionnames[$key] === 'Legacy Caselog') {
                return $id;
            }
        }
        return null;
    }

    /**
     * Try to reuse a pre-versioned category for the legacy version.
     *
     * When importing the legacy version, if a category with the same name
     * already exists at versionid=0 (pre-versioned), we migrate it to the
     * legacy version instead of creating a duplicate. This preserves old
     * field IDs and avoids duplicates.
     *
     * Scenarios:
     * 1. Fresh install (no pre-versioned data):
     *    - get_record(['versionid' => 0]) returns empty → returns null.
     *    - Result: new category + new fields created, identical to pre-change behaviour.
     * 2. Upgrade (pre-versioned data exists, same name):
     *    - get_record(['versionid' => 0]) finds the old category → reused, versionid updated.
     *    - Fields are skipped (old fields are already in place, preserving their IDs).
     *    - Result: no duplicates, old field/category IDs preserved.
     * 3. Idempotent re-run (already migrated, versionid != 0):
     *    - get_record(['versionid' => 0]) returns empty → returns null.
     *    - Result: normal path creates or reuses the already-migrated category.
     * 4. Name mismatch (old category name differs from schema):
     *    - get_record(['versionid' => 0]) returns empty → returns null.
     *    - Old category stays at versionid=0 (handled separately by ensure_case_versions()).
     *    - Result: new category + fields created alongside old one.
     *
     * @param int    $versionid    The DB ID of the version being imported.
     * @param string $categoryname The category name from the schema.
     * @param array  $versionids   Map of version keys to DB IDs.
     * @param array  $versionnames Map of version keys to version names.
     * @param array  $log          Log message array (passed by reference).
     * @param bool   $verbose      Whether to add log messages.
     * @return int|null The reused category ID, or null if no reuse occurred.
     */
    private static function try_reuse_legacy_category(
        int $versionid,
        string $categoryname,
        array $versionids,
        array $versionnames,
        array &$log,
        bool $verbose
    ): ?int {
        $legacyversionid = self::get_legacy_version_id($versionids, $versionnames);
        if ($versionid !== $legacyversionid) {
            return null;
        }

        $oldcat = case_cat::get_record([
            'name' => $categoryname,
            'versionid' => 0,
        ]);
        if (!$oldcat) {
            return null;
        }

        // Reuse the old category and migrate it to the legacy version.
        $oldcat->set('versionid', $versionid);
        $oldcat->update();
        if ($verbose) {
            $msg = "Reused pre-versioned category '{$categoryname}' "
                . "(id: {$oldcat->get('id')}) for legacy version.";
            $log[] = $msg;
        }
        return $oldcat->get('id');
    }
}
