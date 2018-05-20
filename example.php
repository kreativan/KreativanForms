<?php

/**
 *  Form Fields
 *
 */
$form_fields = [
    "name" => [
        "type" => "text",
        "name" => "name",
        "label" => "Your Name",
        "placeholder" => "your name...",
        "required" => true,
        "width" => "1-2",
    ],
    "email" => [
        "type" => "email",
        "name" => "email",
        "label" => "Your Email",
        "placeholder" => "your email address...",
        "required" => true,
        "width" => "1-2",
    ],
    "subject" => [
        "type" => "text",
        "name" => "subject",
        "label" => "Subject",
        "placeholder" => "What is this about m?",
        "required" => false,
        "width" => "1-1",
    ],
    "url" => [
        "type" => "text",
        "name" => "url",
        "label" => "URL",
        "placeholder" => "URL",
        "required" => false,
        "width" => "1-1",
    ],
    "select" => [
        "type" => "select",
        "name" => "select",
        "label" => "Select Options",
        "required" => false,
        "options" => ["option 1", "option 2", "option 3"],
        "width" => "1-2",
    ],
    "number" => [
        "type" => "number",
        "name" => "number",
        "label" => "Number",
        "placeholder" => "5+",
        "required" => false,
        "width" => "1-2",
    ],
    "checkbox" => [
        "type" => "checkbox",
        "name" => "checkbox",
        "label" => "Checkbox Options",
        "options" => ["option 1", "option 2", "option 3"],
        "width" => "1-2",
    ],
    "radio" => [
        "type" => "radio",
        "name" => "radio",
        "label" => "Radio Options",
        "options" => ["option 1", "option 2", "option 3"],
        "width" => "1-2",
    ],
    "message" => [
        "type" => "textarea",
        "name" => "message",
        "label" => "Message",
        "placeholder" => "your message here...",
        "required" => false,
        "width" => "1-1",
        "rows" => "5",
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
$postParams = [
    "submit_button" => "submit",
    "admin_email" => $system->site_info->email,
    "user_email" => "email",
    "subject" => "subject",
    "success_message" => "Message Sent! Thank you!!!"
];
echo $modules->get("KreativanForms")->processForm($postParams);
