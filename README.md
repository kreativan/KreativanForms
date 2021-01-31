# KreativanForms
Processwire Forms Module, based on uikit 3.     

Module supports only CSRF and basic validation (for now), comes with basic fields (file upload, date and time picker included) and custom math captcha (eg: 2 +5 = ?)...

To create forms, you need to define your form fields and execute `renderForm()` method. To process form and send an actual email use `sendEmail()` method.

#### Define Fields in array()
Only required value is array key, type and options (if its a select, radio or checkbox field type). If name is not defined, array key will be used instead, and for the rest of the values defaults will be used.
```
$fields_arr = [
  "my_field" => [
    "type" => "select",
    "name" => "my_field_name",  
    "label" => "My Field",
    "placeholder" => "",
    "required" => true,
    "width" => "1-2", // uikit width class
    "options" => ["option 1", "option 2", "option 3"],
  ],
];
```
#### Render Form
We need to pass few parametars to the `renderForm()` method. Only required param is ***fields***, this is the fields array we defined before. Also, to use multiple forms on a same page, adding custom ***button_name*** is recomended, if empty "*submit*" name will be used.
```
$form = [
  "fields" => $fields_arr, // fields array
	"url" => "./",
  "class" => "my-form-class",
  "id" => "my-form",
  "button_name" => "submit", // submit button name
  "button_text" => "Send Email",
  "button_style" => "primary", // uikit color class
  "button_class" => "", // add some custom class
];
echo $modules->get("KreativanForms")->renderForm($form);
```
#### Processing Form & send email
`sendEmail()` method accepts few params. Only required fields are ***submitName*** (so we know what form to submit) and ***emailTo*** (where to send form to).    
***emailField*** is the form email field name, not actual email address. Its recommended to define it, its used for "replyTo"...
```
$params = [
    "submitName" => "submit",
    "emailTo" => "example@gmail.com",
    "emailFrom" => "from@gmail.com",
    "emailFromName" => "Kreativan.net",
    "emailField" => "email",
    "subject" => "Test Email",
    "message" => "Message Sent! Thank you!!!",
	  "redirect_url" => "./",
];
echo $modules->get("KreativanForms")->sendEmail($params);
```
## Example
Let's create simple contact form
```
<?php

$kreativanForms = $modules->get("KreativanForms");

// set fields
$fields_arr = [
    "name" => [
        "type" => "text",
        "label" => "Name",
        "placeholder" => "What is your Name",
        "required" => true,
        "width" => "1-2",
    ],
    "mail" => [
        "type" => "email",
        "label" => "Your Email",
        "placeholder" => "Email address",
        "required" => true,
        "width" => "1-2",
    ],
    "subject" => [
        "type" => "text",
        "label" => "Subject",
        "placeholder" => "What is this about?",
        "width" => "1-1",
    ],
    "message" => [
        "type" => "textarea",
        "label" => "Message",
        "placeholder" => "Your message...",
        "width" => "1-1",
        "rows" => "7"
    ]
];

// render form
$form = [
    "fields" => $fields_arr,
    "button_name" => "submit_form",
    "button_text" => "Contact Us",
];
echo $kreativanForms->renderForm($form);

// send email params
$params = [
    "submitName" => "submit_form",
    "emailTo" => "example@gmail.com",
    "emailFrom" => "from@gmail.com",
    "emailFromName" => "Kreativan Forms"
    "emailField" => "mail", // email field name used for replyTo
    "subject" => "Hello",
    "message" => "Message Sent! Thank you!!!"
];
echo $kreativanForms->sendEmail($params);

```
