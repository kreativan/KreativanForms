<?php

/**
 *  Form Fields
 *
 */
$form_fields = [
    "name" => [
        "type" => "text",
        "label" => "Your Name",
        "placeholder" => "your name...",
        "required" => true,
        "width" => "1-2",
    ],
    "email" => [
        "type" => "email",
        "label" => "Your Email",
        "placeholder" => "your email address...",
        "required" => true,
        "width" => "1-2",
    ],
    "subject" => [
        "type" => "text",
        "label" => "Subject",
        "placeholder" => "What is this about m?",
        "required" => false,
        "width" => "1-1",
    ],
    "url" => [
        "type" => "text",
        "label" => "URL",
        "placeholder" => "URL",
        "required" => false,
        "width" => "1-1",
    ],
    "select" => [
        "type" => "select",
        "label" => "Select Options",
        "required" => false,
        "options" => ["option 1", "option 2", "option 3"],
        "width" => "1-2",
    ],
    "number" => [
        "type" => "number",
        "label" => "Number",
        "placeholder" => "5+",
        "required" => false,
        "width" => "1-2",
    ],
    "checkbox" => [
        "type" => "checkbox",
        "label" => "Checkbox Options",
        "options" => ["option 1", "option 2", "option 3"],
        "width" => "1-2",
    ],
    "radio" => [
        "type" => "radio",
        "label" => "Radio Options",
        "options" => ["option 1", "option 2", "option 3"],
        "width" => "1-2",
    ],
    "date" => [
        "type" => "date",
        "label" => "Pick a Date",
        "placeholder" => "Pick a Date",
        "required" => true,
        "width" => "1-2",
    ],
    "time" => [
        "type" => "time",
        "label" => "Pick a Time",
        "placeholder" => "Pick a Time",
        "required" => true,
        "width" => "1-2",
    ],
    "message" => [
        "type" => "textarea",
        "label" => "Message",
        "placeholder" => "your message here...",
        "required" => false,
        "width" => "1-1",
        "rows" => "5",
    ],
    "my_file" => [
        "type" => "file",
        "label" => "File",
        "placeholder" => "Select File",
        "required" => false,
        "width" => "1-1",
    ],
];

/**
 *  Render Form
 *
 */
$form = [
    "fields" => $form_fields,
    "class" => "my-form-class",
    "id" => "my-form",
    "button_name" => "submit",
    "button_text" => "Send Email",
    "button_style" => "primary",
    "button_class" => "",
];
echo $modules->get("KreativanForms")->renderForm($form);

/**
 *  Process Form
 *
 */
$params = [
    "submit_button" => "submit",
    "admin_email" => $system->site_info->email,
    "user_email" => "email",
    "subject" => "subject",
    "success_message" => "Message Sent! Thank you!!!"
];
$params = [
  "submitName" => "submit_form",
  "emailTo" => "example@gmail.com",
  "emailFrom" => "from@gmail.com",
  "emailFromName" => "Kreativan Forms"
  "emailField" => "mail", // form email field, used for replyTo
  "subject" => "Hello",
  "message" => "Message Sent! Thank you!!!"
];
echo $modules->get("KreativanForms")->sendEmail($params);
