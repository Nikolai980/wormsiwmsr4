<?php
use phpformbuilder\Form;
use phpformbuilder\Validator\Validator;
use fileuploader\server\FileUploader;

/* =============================================
    start session and include form class
============================================= */

session_start();
include_once rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . '/phpformbuilder/Form.php';

// include the fileuploader

include_once rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . '/phpformbuilder/plugins/fileuploader/server/class.fileuploader.php';

/* =============================================
    validation if posted
============================================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('fg-form')) {
    // create validator & auto-validate required fields
    $validator = Form::validate('fg-form');

    // additional validation
    $validator->email()->validate('email');

    // recaptcha validation
    $validator->recaptcha('6LeCjicdAAAAAJUmU50XgYzTdnqL77vKeZ8zZgOj', 'Recaptcha Error')->validate('g-recaptcha-response');

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['fg-form'] = $validator->getAllErrors();
    } else {
        $uploaded_files = [];
        if (isset($_POST['uploader-1']) && !empty($_POST['uploader-1'])) {
            $posted_file = FileUploader::getPostedFiles($_POST['uploader-1']);
            $uploaded_files['uploader-1'] = [
                'upload_dir' => '/home/phpformbuilder/public_html/phpformbuilder/plugins/fileuploader/default/php/../../../../../file-uploads/',
                'filename' => $posted_file[0]['file']
            ];
        }
        /* Send email with attached file(s) */
        $attachments = array();
        foreach ($uploaded_files as $f) {
            $attachments[] = $f['upload_dir'] . $f['filename'];
        }
        $attachments = implode(', ', $attachments);
        $email_config = array(
            'sender_email'    => 'admin@r4-club.com',
            'recipient_email' => 'admin@r4-club.com',
            'subject'         => 'New contact us message',
            'attachments'    =>  $attachments,
            'filter_values'   => 'fg-form, uploader-1, uploader-uploader-1'
        );
        $sent_message = Form::sendMail($email_config);
        // clear the form
        Form::clear('fg-form');
        // redirect after success
        header('Location:https://r4-club.com/en/thank-you.html');
        exit;
    }
}

/* ==================================================
    The Form
 ================================================== */

$form = new Form('fg-form', 'vertical', 'novalidate, data-fv-no-icon=true', 'bs5');
// enable Ajax loading
$form->setOptions(['ajax' => true]);

// $form->setMode('development');
$form->addHeading('Contact Form', 'h3', '');
$form->addIcon( 'name', '<i class="fa-solid fa-user-large" aria-label="hidden"></i>', 'before');
$form->addInput('text', 'name', '', '', 'placeholder=Name');
$form->addIcon( 'email', '<i class="fa-solid fa-inbox" aria-label="hidden"></i>', 'before');
$form->addInput('email', 'email', '', '', 'placeholder=Email,required=required');
$form->addTextarea('textarea-1', '', '', 'required=required,placeholder=Your Message\, question\, suggestion or inquiry');
$form->addHelper('You can only upload: jpg, jpeg, png, gif, zip, tar and img extension files.', 'uploader-1');

// Prefill upload with existing file
$current_file = ''; // default empty

$current_file_path = '../../../../../file-uploads/';

/* INSTRUCTIONS:
    If you get a filename from your database or anywhere
    and want to prefill the uploader with this file,
    replace "filename.ext" with your filename variable in the line below.
*/
$current_file_name = 'filename.ext';

if (file_exists($current_file_path . $current_file_name)) {
    $current_file_size = filesize($current_file_path . $current_file_name);
    $current_file_type = mime_content_type($current_file_path . $current_file_name);
    $current_file = array(
        'name' => $current_file_name,
        'size' => $current_file_size,
        'type' => $current_file_type,
        'file' => $current_file_path . $current_file_name, // url of the file
        'data' => array(
            'listProps' => array(
                'file' => $current_file_name
            )
        )
    );
}

$fileUpload_config = array(
    'upload_dir'    => '../../../../../file-uploads/',
    'limit'         => 3,
    'file_max_size' => 50,'extensions'    => ['jpg', 'jpeg', 'png', 'gif', 'zip', 'tar', 'img'],
    'debug'         => true
);
$form->addFileUpload('uploader-1', '', 'Click below to upload content related to your case', '', $fileUpload_config, $current_file);
$form->addCheckbox('checkbox-1', 'Sending this form you agree to our Terms of Service and understand that your IP address will be collected for security purposes.', 'value-1', 'data-toggle=false');
$form->centerContent();
$form->printCheckboxGroup('checkbox-1', '', true, 'required=required');
$form->centerContent(false);
$form->addHtml('<span class="label-text" style="font-size: 13px;">Read our <a href="r4-club.com/en/terms">Terms of Service</a></span>');
$form->addRecaptchaV3('6LeCjicdAAAAAAjoxC-Zq3JgMoLhb7cvo8xs4R7p');
$form->centerContent();
$form->addBtn('submit', 'button-2', '', 'Send now 📩', 'class=btn btn-success,data-ladda-button=true,data-style=zoom-out');
$form->centerContent(false);
$form->addPlugin('formvalidation', '#fg-form', 'default', array('language' => 'en_US'));

if (isset($sent_message)) {
    echo $sent_message;
}

$form->render();
?>