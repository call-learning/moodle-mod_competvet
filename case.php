<?php
require(__DIR__ . '/../../config.php');

use mod_competvet\competvet;
use mod_competvet\form\case_form_page;
use mod_competvet\local\api\cases;
use mod_competvet\local\persistent\case_entry;

$cmid = required_param('cmid', PARAM_INT);
$planningid = required_param('planningid', PARAM_INT);
$studentid = optional_param('studentid', $USER->id, PARAM_INT);
$entryid = optional_param('entryid', 0, PARAM_INT);
$cm = get_coursemodule_from_id('competvet', $cmid, 0, false, MUST_EXIST);
$competvet = competvet::get_from_cmid($cmid);
$context = $competvet->get_context();
require_login($cm->course, true, $cm);
require_capability('mod/competvet:view', $context);

$returnurl = new moodle_url('/mod/competvet/view.php', ['id' => $cmid]);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/competvet/case.php', [
    'cmid' => $cmid, 'planningid' => $planningid, 'studentid' => $studentid, 'entryid' => $entryid,
]));
$PAGE->set_title($entryid ? get_string('caselog:edittitle', 'mod_competvet') : get_string('caselog:addtitle', 'mod_competvet'));

$entry = $entryid ? case_entry::get_record(['id' => $entryid]) : null;
if ($entry && !$entry->can_edit()) {
    require_capability('mod/competvet:caneditother', $context);
}
$form = new case_form_page();
if ($form->is_cancelled()) {
    redirect($returnurl);
}
if ($data = $form->get_data()) {
    try {
        $status = !empty($data->save_draft) ? 'draft' : 'validated';
        $fields = $form->get_case_fields($data);
        if ($entryid) {
            cases::update_case($entryid, $fields, $status);
        } else {
            cases::create_case($data->planningid, $data->studentid, $fields, $status);
        }
        redirect($returnurl);
    } catch (moodle_exception $exception) {
        $form->addError($exception->getMessage());
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($PAGE->title);
$form->display();
echo $OUTPUT->footer();
