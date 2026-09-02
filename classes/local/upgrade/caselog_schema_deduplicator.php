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

namespace mod_competvet\local\upgrade;

/**
 * Remove duplicate Caselog definitions without losing references or values.
 *
 * @package    mod_competvet
 * @copyright  2026 CALL Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class caselog_schema_deduplicator {
    /**
     * Merge duplicate versions, categories and fields into their oldest records.
     *
     * @return void
     */
    public static function execute(): void {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        try {
            $versions = $DB->get_records('competvet_case_version', null, 'name, id');
            $canonicalversions = [];
            foreach ($versions as $version) {
                if (!isset($canonicalversions[$version->name])) {
                    $canonicalversions[$version->name] = $version;
                    continue;
                }
                self::merge_version($canonicalversions[$version->name], $version);
            }

            foreach ($canonicalversions as $version) {
                self::merge_categories($version->id);
            }
            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
            throw $e;
        }
    }

    /**
     * Reconcile schema fields that were imported under the wrong category.
     *
     * @param string $filepath Path to the Caselog schema JSON file.
     * @return void
     */
    public static function reconcile_schema_fields(string $filepath): void {
        global $DB;

        $schema = json_decode((string)file_get_contents($filepath), true);
        if (!is_array($schema) || !isset($schema['versions'], $schema['categories'])) {
            throw new \moodle_exception('Invalid Caselog schema.');
        }

        $versions = $DB->get_records('competvet_case_version');
        $categories = $DB->get_records('competvet_case_cat');
        $categoryversions = [];
        foreach ($categories as $category) {
            $categoryversions[$category->id] = $category->versionid;
        }

        $versionids = [];
        foreach ($schema['versions'] as $versiondata) {
            foreach ($versions as $version) {
                $metadata = json_decode((string)$version->metadata, true);
                if (($metadata['schemaid'] ?? null) === $versiondata['key'] || $version->name === $versiondata['name']) {
                    $versionids[$versiondata['key']] = $version->id;
                    break;
                }
            }
        }

        $expectedcategories = [];
        foreach ($schema['categories'] as $categorydata) {
            $versionid = $versionids[$categorydata['versionkey']] ?? null;
            if ($versionid === null) {
                continue;
            }
            $categorykey = $categorydata['idnumber'] ?? 'category' . substr(md5($categorydata['name']), 0, 12);
            foreach ($categories as $category) {
                if ($category->versionid !== $versionid) {
                    continue;
                }
                if ($category->idnumber === $categorykey || $category->name === $categorydata['name']) {
                    $expectedcategories[$versionid . '|' . $categorykey] = $category->id;
                    break;
                }
            }
        }

        foreach ($schema['categories'] as $categorydata) {
            $versionid = $versionids[$categorydata['versionkey']] ?? null;
            $categorykey = $categorydata['idnumber'] ?? 'category' . substr(md5($categorydata['name']), 0, 12);
            $targetcategoryid = $expectedcategories[$versionid . '|' . $categorykey] ?? null;
            if ($targetcategoryid === null) {
                continue;
            }

            foreach ($categorydata['fields'] ?? [] as $fielddata) {
                $fields = $DB->get_records('competvet_case_field', ['idnumber' => $fielddata['idnumber']], 'id');
                $target = null;
                foreach ($fields as $field) {
                    if ((int)$field->categoryid === $targetcategoryid) {
                        $target = $field;
                        break;
                    }
                }
                foreach ($fields as $field) {
                    if (
                        (int)$field->categoryid === $targetcategoryid
                            || ($categoryversions[$field->categoryid] ?? null) !== $versionid
                    ) {
                        continue;
                    }
                    if ($target) {
                        self::merge_field_data($target->id, $field->id);
                        self::merge_field_mappings($target->id, $field->id);
                        $DB->delete_records('competvet_case_field', ['id' => $field->id]);
                    } else {
                        $DB->set_field('competvet_case_field', 'categoryid', $targetcategoryid, ['id' => $field->id]);
                        $target = $field;
                    }
                }
            }
        }
    }

    /**
     * Merge a duplicate version into the canonical version.
     *
     * @param object $canonical
     * @param object $duplicate
     * @return void
     */
    private static function merge_version(object $canonical, object $duplicate): void {
        global $DB;

        $DB->set_field('competvet_case_entry', 'versionid', $canonical->id, ['versionid' => $duplicate->id]);
        $categories = $DB->get_records('competvet_case_cat', ['versionid' => $duplicate->id], 'id');
        foreach ($categories as $category) {
            $category->versionid = $canonical->id;
            $DB->update_record('competvet_case_cat', $category);
        }
        self::merge_categories($canonical->id);
        $DB->delete_records('competvet_case_version', ['id' => $duplicate->id]);
    }

    /**
     * Merge duplicate categories within a version.
     *
     * Names are used deliberately for the first cleanup because the original
     * schema did not have stable category identifiers.
     *
     * @param int $versionid
     * @return void
     */
    private static function merge_categories(int $versionid): void {
        global $DB;

        $categories = $DB->get_records('competvet_case_cat', ['versionid' => $versionid], 'name, id');
        $canonical = [];
        foreach ($categories as $category) {
            if (!isset($canonical[$category->name])) {
                $canonical[$category->name] = $category;
                continue;
            }
            self::merge_category($canonical[$category->name], $category);
        }
    }

    /**
     * Merge a duplicate category into the oldest category.
     *
     * @param object $canonical
     * @param object $duplicate
     * @return void
     */
    private static function merge_category(object $canonical, object $duplicate): void {
        global $DB;

        $fields = $DB->get_records('competvet_case_field', ['categoryid' => $duplicate->id], 'id');
        foreach ($fields as $field) {
            $existing = $DB->get_record('competvet_case_field', [
                'categoryid' => $canonical->id,
                'idnumber' => $field->idnumber,
            ]);
            if ($existing) {
                self::merge_field_data($existing->id, $field->id);
                self::merge_field_mappings($existing->id, $field->id);
                $DB->delete_records('competvet_case_field', ['id' => $field->id]);
            } else {
                $DB->set_field('competvet_case_field', 'categoryid', $canonical->id, ['id' => $field->id]);
            }
        }
        $DB->delete_records('competvet_case_cat', ['id' => $duplicate->id]);
    }

    /**
     * Repoint duplicate field data, refusing conflicting non-empty values.
     *
     * @param int $canonicalid
     * @param int $duplicateid
     * @return void
     */
    private static function merge_field_data(int $canonicalid, int $duplicateid): void {
        global $DB;

        $rows = $DB->get_records('competvet_case_data', ['fieldid' => $duplicateid], 'id');
        foreach ($rows as $row) {
            $duplicaterowid = $row->id;
            $existing = $DB->get_record('competvet_case_data', [
                'entryid' => $row->entryid,
                'fieldid' => $canonicalid,
            ]);
            if (!$existing) {
                $DB->set_field('competvet_case_data', 'fieldid', $canonicalid, ['id' => $row->id]);
                continue;
            }
            if (self::has_value($existing) && self::has_value($row)) {
                throw new \moodle_exception('Conflicting data found while merging Caselog fields.');
            }
            if (!self::has_value($existing) && self::has_value($row)) {
                $row->id = $existing->id;
                $row->fieldid = $canonicalid;
                $DB->update_record('competvet_case_data', $row);
            }
            $DB->delete_records('competvet_case_data', ['id' => $duplicaterowid]);
        }
    }

    /**
     * Merge duplicate situation-to-field mappings.
     *
     * @param int $canonicalid
     * @param int $duplicateid
     * @return void
     */
    private static function merge_field_mappings(int $canonicalid, int $duplicateid): void {
        global $DB;

        $mappings = $DB->get_records('competvet_case_fields', ['fieldid' => $duplicateid]);
        foreach ($mappings as $mapping) {
            $exists = $DB->record_exists('competvet_case_fields', [
                'fieldid' => $canonicalid,
                'situationid' => $mapping->situationid,
            ]);
            if ($exists) {
                $DB->delete_records('competvet_case_fields', ['id' => $mapping->id]);
            } else {
                $DB->set_field('competvet_case_fields', 'fieldid', $canonicalid, ['id' => $mapping->id]);
            }
        }
    }

    /**
     * Check whether a case-data row contains a value.
     *
     * @param object $row
     * @return bool
     */
    private static function has_value(object $row): bool {
        return $row->intvalue !== null && (int)$row->intvalue !== 0
            || $row->decvalue !== null && (float)$row->decvalue !== 0.0
            || $row->shortcharvalue !== null && $row->shortcharvalue !== ''
            || $row->charvalue !== null && $row->charvalue !== ''
            || $row->textvalue !== null && $row->textvalue !== '';
    }
}
