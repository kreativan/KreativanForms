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
    "message" => [
        "type" => "textarea",
        "name" => "message",
        "label" => "Message",
        "placeholder" => "your message here...",
        "required" => true,
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
echo $modules->get("KappsForms")->renderForm($form);

/**
 *  Process Form
 *
 */
$postParams = [
    "submit" => "submit",
    "admin_email" => $system->site_info->email,
    "user_email" => "email",
    "subject" => "subject",
    "success_message" => "Message Sent! Thank you!!!"
];
echo $modules->get("KappsForms")->processForm($postParams);
