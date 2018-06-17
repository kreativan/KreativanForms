<?php
/**
 *  KreativanForms Module
 *
 *  @author Ivan Milincic <lokomotivan@gmail.com>
 *  @copyright 2018 Ivan Milincic
 *
 *
*/

class KreativanForms extends WireData implements Module {

    public static function getModuleInfo() {
        return array(
            'title' => 'Forms',
            'version' => 100,
            'summary' => 'Kreativan Forms...',
            'icon' => 'wpforms',
            'singular' => true,
            'autoload' => true
        );
    }

    public function init() {


    }

    public function executeEdit() {

    }

    /**
     *  Validate Form CSRF & Captcha
     *  eg: ($modules->get("KreativanForms")->formValidate() == true) ? "YEAH" : "NO NO";
     *
     */
    public function formValidate() {

        if($this->session->CSRF->hasValidToken()) {

            $numb_captcha = $this->sanitizer->text($this->input->post->numb_captcha);
            $captcha_answer = $this->sanitizer->text($this->input->post->captcha_answer);

            if($numb_captcha == $captcha_answer) {
                return true;
            }

        }

    }

    /**
     *  Form Process
     *
     *  @param postParams main param array
     *
     *  @param submit_button string form submit button name
     *  @param admin_email admin email
     *  @param user_email field that will be sued as user email for replyTo
     *  @param subject  field that will be used for subject
     *  @param success_message string
     *
     *  @example echo $modules->get("KreativanForms")->processForm($postParams);
     *
     */
    public function processForm($postParams = "") {

        // main var that we will return at the end
        $form_process = "";

        // submit button name
        $submit_button = !empty($postParams['submit_button']) ? $postParams['submit_button'] : 'submit';

        // is form is submited process...
        if($this->input->post->{$submit_button}) {

            if($this->formValidate() == true) {

                // admin email, send mail to
                $adminEmail = !empty($postParams['admin_email']) ? $postParams['admin_email'] : "";
                // user email field for replayTo
                $email_field = !empty($postParams['user_email']) ? $postParams['user_email'] : "";
                $email = $_POST["$email_field"];
                // subject field
                $subject_field = !empty($postParams['subject']) ? $postParams['subject'] : "";
                $subject = $_POST["$subject_field"];
                // success message after processing the form
                $success_message = !empty($postParams['success_message']) ? $postParams['success_message'] : __("Your message has been sent");

                // emial body
                $email_body = "";

                // remove token from post array toexlude it from email_body
                array_pop($_POST);
                // fields not to include in email_body
                $exclude_fields = ["$submit_button", "captcha_answer", "numb_captcha"];

                // loop true $_POST and update $email_body
                foreach($_POST as $key => $value) {
                    if(!empty($value) && !in_array($key, $exclude_fields)) {
                        // check if posted value is array (eg: cheboxes values)
                        // if it is, implode it to string
                        if(is_array($value)) {
                            $value = implode(",", $value);
                            $value = nl2br($value);
                        } else {
                            $value = strip_tags($value);
                            $value = nl2br($value);
                        }
                        $label = str_replace("_", " ", $key);
                        $label = ucfirst($label);
                        $email_body .= "<p><b>$label:</b><br /> $value</p>";
                    }
                }

                // File Upload (using wireMail for attachments)
                // ========================================================================
                if(!empty($_FILES)) {
                    // files array
                    $files_arr = [];
                    // upload files
                    $form_process .= $this->fileUpload();
                    // create array of files
                    foreach($_FILES as $file) {
                        $files_arr[] .= $this->config->paths->files."_tmp/$file[name]";
                    }

                    // send email
                    $m = wireMail();
                    $m->to($adminEmail); // specify CSV string or array for multiple addresses
                    $m->from($email);
                    $m->subject($subject);
                    $m->bodyHTML($email_body);
                    if(!empty($files_arr)) {
                        $f = 0;
                        foreach($_FILES as $file) {
                            $m->attachment($files_arr[$f++]);
                        }
                    }
                    $m->send();

                } else {
                    // if no files send basic mail
                    // ========================================================================
                    $email_to       = $adminEmail;
                    $email_subject  = $subject;
                    $email_from     = $email;
                    mail("$email_to", "$email_subject", "$email_body", "From: $email_from\nContent-Type: text/html");
                }

                // Delete Temp Files
                // ========================================================================
                $form_process .= $this->deleteTempFiles();

                // set success session alert
                $this->session->set("status", "primary");
                $this->session->set("alert", "$success_message");

                // redirect
                header("Location: {$this->page->url}");
                exit();

            } else {

                // set error session alert
				$this->session->set("status", "danger");
                $this->session->set("alert", "There was an error! Please fill in all required fields.");

                // redirect
                header("Location: {$this->page->url}");
                exit();

            }

        }

        return $form_process;

    }

    /**
     *  Render Form
     *
     *  Form Params
     *  @param form main params array
     *  @param fields fields array, with fields params below
     *  @param class form css class
     *  @param id  form css id
     *  @param button_name
     *  @param button_text
     *  @param button_style
     *  @param button_class
     *
     *  Fields Array Params
     *  @param type string (tetx, email, textarea...)
     *  @param name string (no space allowed)
     *  @param label string
     *  @param placeholder  string
     *  @param required bool (true/false)
     *  @param width string (uikit grid widtheg: 1-2)
     *  @param rows int (for textarea)
     *  @param options array
     *
     */
    public function renderForm($form) {

        // used to detect if there is date or time field in form
        // so we can load or exlude flatpickr
        $is_date = false;
        $is_time = false;

        // form start
        $form_markup = "";

        /**
         *  Display success / error alert
         *
         */
        if($this->session->get("alert")) {
            $form_markup .= "
                <div class='uk-alert-{$this->session->get("status")}' uk-alert>
                    <a class='uk-alert-close' uk-close></a>
                    <p>{$this->session->get("alert")}</p>
                </div>
            ";
            $this->session->remove('status');
			$this->session->remove('alert');
        }

        // main form params
        $form_fields    = $form["fields"];
        $form_class     = !empty($form['class']) ? "{$form['class']} " : "";
        $form_id        = !empty($form['id']) ? $form['id'] : "";
        $button_name    = !empty($form['button_name']) ? $form['button_name'] : "submit";
        $button_style   = !empty($form['button_style']) ? $form['button_style'] : "primary";
        $button_class   = !empty($form['button_class']) ? "{$form['button_class']} " : "";
        $button_text    = !empty($form['button_text']) ? $form['button_text'] : "Submit Form";

        // numb captcha
        $numb_1 = rand(1, 5);
        $numb_2 = rand(1, 5);
        $numb_q = "$numb_1 + $numb_2 =";
        $answer = $numb_1 + $numb_2;

        // form markup
        $form_markup .= "<form id='$form_id' action='./' method='POST' enctype='multipart/form-data' class='{$form_class}'><div class='uk-grid-small' uk-grid>";

            foreach($form_fields as $key => $field) {
                // field vars
                $type           = !empty($field["type"]) ? $field["type"] : "text";
                $name           = !empty($field["name"]) ? $field["name"] : str_replace(" ", "_", $key);
                $label          = !empty($field["label"]) ? $field["label"] : "";
                $placeholder    = !empty($field["placeholder"]) ? $field["placeholder"] : "";
                $required       = (!empty($field["required"]) && $field["required"] == true) ? true : false;
                $width          = !empty($field["width"]) ? $field["width"] : "1-1";
                $rows           = !empty($field["rows"]) ? $field["rows"] : "5";
                $options        = !empty($field['options']) ? $field['options'] : "";

                $label_html = !empty($label) ? "<label class='uk-form-label'>$label</label>" : "";

                // required sttribute
                $required_attr = ($required == true) ? "required" : "";


                $form_markup .= "<div class='uk-width-$width@m'><div>";

                    if ($field["type"] == "text") {
                        $form_markup .= "
                            $label_html
                            <input class='uk-input' type='text' name='$name' placeholder='$placeholder' $required_attr />
                        ";
                    } elseif ($field["type"] == "email") {
                        $form_markup .= "
                            $label_html
                            <input class='uk-input' type='email' name='$name' placeholder='$placeholder' $required_attr />
                        ";
                    } elseif ($field["type"] == "textarea") {
                        $form_markup .= "
                            $label_html
                            <textarea class='uk-textarea' rows='$rows' name='$name'  placeholder='$placeholder' $required_attr></textarea>
                        ";
                    } elseif ($field["type"] == "number") {
                        $form_markup .= "
                            $label_html
                            <input class='uk-input' type='number' name='$name' placeholder='$placeholder' $required_attr />
                        ";
                    } elseif ($field["type"] == "url") {
                        $form_markup .= "
                            $label_html
                            <input class='uk-input' type='url' name='$name' placeholder='$placeholder' $required_attr />
                        ";
                    } elseif ($field["type"] == "select") {
                        $form_markup .= $label_html;
                        $form_markup .= "<select class='uk-select' name='$name'>";
                            foreach($options as $option) {
                                $form_markup .= "<option value='$option'>$option</option>";
                            }
                        $form_markup .= "</select>";
                    } elseif ($field["type"] == "checkbox") {
                        $form_markup .= $label_html;
                            $form_markup .= "<div>";
                                foreach($options as $option) {
                                    $form_markup .= "
                                        <label class='uk-form-label uk-margin-small-right'>
                                            <input class='uk-checkbox' type='checkbox' name='{$name}[]' value='$option' />
                                            <span>$option</span>
                                        </label>
                                    ";
                                }
                            $form_markup .= "</div>";
                    } elseif ($field["type"] == "radio") {
                        $form_markup .= $label_html;
                            $form_markup .= "<div>";
                                $i = 0;
                                foreach($options as $option) {
                                    $checked = $i++ == 0 ? "checked" : "";
                                    $form_markup .= "
                                        <label class='uk-form-label uk-margin-small-right'>
                                            <input class='uk-radio' type='radio' name='{$name}' value='$option' $checked />
                                            <span>$option</span>
                                        </label>
                                    ";
                                }
                            $form_markup .= "</div>";
                    } elseif ($field["type"] == "date") {
                        $is_date = true;
                        $form_markup .= $label_html;
                        $form_markup .= "<input class='datePicker uk-input' type='text' name='$name' placeholder='$placeholder' $required_attr />";
                    } elseif ($field["type"] == "time") {
                        $is_time = true;
                        $form_markup .= "<label class='uk-form-label'>$label</label>";
                        $form_markup .= "<input class='timePicker uk-input' type='text' name='$name' placeholder='$placeholder' $required_attr />";
                    } elseif ($field["type"] == "file") {
                        $form_markup .= "<label class='uk-form-label uk-display-block'>$label</label>";
                        $form_markup .= "
                            <div class='uk-width-1-1' uk-form-custom='target: true'>
                                <input type='file' name='$name'>
                                <input class='uk-input' type='text' placeholder='$placeholder' disabled>
                            </div>
                        ";
                    }

                $form_markup .= "</div></div>";
            }

            $form_markup .= "<div class='uk-flex uk-width-1-1 uk-margin-top'>";
                // captcha
                $form_markup .= "
                    <div class='uk-grid-collapse' uk-grid>
                        <div class='uk-width-auto uk-flex uk-flex-middle'>
                            <label class='uk-h3'>$numb_q</label>
                        </div>
                        <div class='uk-width-auto'>
                            <input class='numb-captcha-answer uk-hidden' type='text' name='captcha_answer' value='$answer' required />
                            <input class='numb-captcha-q uk-input uk-form-width-xsmall uk-margin-small-left uk-text-center' type='text' name='numb_captcha' placeholder='?' required />
                        </div>
                    </div>
                    <div class='uk-margin-left'>
                        <input type='submit' name='$button_name' class='{$button_class}uk-button uk-button-$button_style' value='$button_text' />
                    </div>
                ";
            $form_markup .= "</div>";

        $form_markup .= $this->session->CSRF->renderInput() . "</div></form>";

        if($is_date == true || $is_time == true) {
            return $this->flatpickr($form_id) . $form_markup;
        } else {
            return $form_markup;
        }

    }


    /**
     *  Load and init flatpickr
     *
     *  @param form_id string form css id
     *
     *
     */
    public function flatpickr($form_id) {

        $module_folder = $this->config->urls->siteModules . "KreativanForms/";

        // flatpickr files
        $cssFile = $module_folder . "flatpickr/flatpickr.min.css";
        $jsFile = $module_folder . "flatpickr/flatpickr.min.js";

        $this->config->styles->add($cssFile);
        $this->config->scripts->add($jsFile);
        // $this->config->scripts->add($module_folder . "forms.js");

        // flatpickr lang files
        $locale = strtolower($this->user->language->title);
        if($locale != "en") {
            $locale_file = $module_folder . "flatpickr/l10n/" . $locale . ".js";
            $this->config->scripts->add($locale_file);
        }

        $script = "
            <script>
                document.addEventListener('DOMContentLoaded', function(){
                    var dateFields = document.querySelectorAll('#{$form_id} .datePicker');
                    var timeFields = document.querySelectorAll('#{$form_id} .timePicker');

                    // set locale
                    flatpickr.localize(flatpickr.l10ns.{$locale});

                    // init date pickers
                    dateFields.forEach(e => {
                        e.flatpickr({
                            dateFormat: 'd-M-Y',
                            altInput: true,
                            altFormat: 'd-M-Y',
                            minDate: 'today',
                            // enableTime: true,
                        });
                    });

                    // init time pickers
                    timeFields.forEach(e => {
                        e.flatpickr({
                            enableTime: true,
                            noCalendar: true,
                            dateFormat: 'H:i',
                            time_24hr: true
                        });
                    });

                });
            </script>
        ";

        return $script;

    }

    public function fileUpload() {

        // destination dir
        $storeFolder = $this->config->paths->files."_tmp/";

        // if dir doesnt exist, create it
        if(!file_exists($storeFolder) && !is_dir($storeFolder)) {
            mkdir($storeFolder);
        }

        foreach($_FILES as $file) {
            $tempFile = $file["tmp_name"];
            $targetFile =  $storeFolder. $file['name'];
            move_uploaded_file($tempFile,$targetFile);
        }

    }

    public function deleteTempFiles() {
        /**
         * Delete temp files
         */
        if(!empty($_FILES)) {
            foreach($_FILES as $file) {
                $tmp_file = $this->config->paths->files."_tmp/$file[name]";
                if(file_exists($tmp_file)) {
                    unlink($tmp_file);
                }
            }
        }
    }


}
