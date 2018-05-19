<?php
/**
 *  KreativanForms Module
 *
 *  @author Ivan Milincic <kreativan@outlook.com>
 *  @copyright 2018 Ivan Milincic
 *
 *
*/

class KreativanForms extends WireData implements Module {

   public static function getModuleInfo() {
        return array(
            'title' => 'Kreativan Forms',
            'version' => 100,
            'summary' => 'Kreativan forms helper module',
            'icon' => 'wpforms',
            'singular' => true,
            'autoload' => true
        );
    }

    public function init() {

    }

    /**
     *  Validate Form CSRF & Captcha
     *  eg: ($modules->get("KappsForms")->formValidate() == true) ? "YEAH" : "NO NO";
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
     *  @param postParams main param array
     *  @param submit string form submit button name
     *  @param admin_email admin email
     *  @param user_email field that will be sued as user email for replyTo
     *  @param subject  field that will be used for subject
     *  @param success_message string
     *
     *      $postParams = [
     *          "submit" => "my_submit_button_name",
     *          "admin_email" => $system->site_info->email,
     *          "user_email" => "email_field",
     *          "subject" => "subject_field"
     *      ];
     *
     *  @example echo $modules->get("KappsForms")->processForm($postParams);
     *
     */
    public function processForm($postParams = "") {

        // submit button name
        $submit = !empty($postParams['submit']) ? $postParams['submit'] : 'submit';

        if($this->input->post->{$submit}) {

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
                $success_message = !empty($postParams['success_message']) ? $postParams['success_message'] : "Your message has been sent";

                // emial body
                $email_body = "";

                // remove token from post array toexlude it from email_body
                array_pop($_POST);
                // fields not to include in email_body
                $exclude_fields = ["$submit", "captcha_answer", "numb_captcha"];

                // loop true $_POST and update $email_body
                foreach($_POST as $key => $value) {
                    if(!empty($value) && !in_array($key, $exclude_fields)) {
                        $value = strip_tags($value);
                        $value = nl2br($value);
                        $label = str_replace("_", " ", $key);
                        $label = ucfirst($label);
                        $email_body .= "<p><b>$label:</b><br /> $value</p>";
                    }
                }

                $email_to       = $adminEmail;
                $email_subject  = $subject;
                $email_from     = $email;
                mail("$email_to", "$email_subject", "$email_body", "From: $email_from\nContent-Type: text/html");

                $_SESSION['status'] = "primary";
                $_SESSION['alert'] = "$success_message";

                // redirect
                header("Location: {$this->page->url}");
                exit();

            } else {

                $_SESSION['status'] = "danger";
                $_SESSION['alert'] = __("There was an error! Please fill in all required fields.");

                // redirect
                header("Location: {$this->page->url}");
                exit();

            }

        }

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
     *  Ser params example:
     *      $form = [
     *          "fields" => $fields_arr,
     *          "class" => "my-form-class",
     *          "id" => "my-form-id",
     *          "button_name" => "submit",
     *          "button_text" => "Submit Form",
     *          "button_style" => "primary",
     *          "button_class" => "some-aditional-class",
     *      ];
     *  Exec form:
     *      echo $modules->get("KappsForms")->renderForm($form);
     *
     *  Fields Array Params
     *  @param type string (tetx, email, textarea...)
     *  @param name string (no space allowed)
     *  @param label string
     *  @param placeholder  string
     *  @param required bool (true/false)
     *  @param width string (uikit grid widtheg: 1-2)
     *  @param rows int
     *
     *  Fields Array Example:
     *
     *     $fields_arr = [
     *         "email" => [
     *             "type" => "email",
     *             "name" => "email_address",
     *             "label" => "Your Email",
     *             "placeholder" => "type in your email",
     *             "required" => true,
     *             "width" => "1-2",
     *         ],
     *     ];
     *
     */
    public function renderForm($form) {

        $form_markup = "";

        if(isset($_SESSION['alert'])) {
            $form_markup .= "
                <div class='uk-alert-{$_SESSION['status']}' uk-alert>
                    <a class='uk-alert-close' uk-close></a>
                    <p>{$_SESSION['alert']}</p>
                </div>
            ";
            unset($_SESSION['status']);
            unset($_SESSION['alert']);
        }

        // main form params
        $form_fields    = $form["fields"];
        $form_class     = !empty($form['class']) ? "{$form['class']} " : "";
        $form_id        = !empty($form['id']) ? "id='{$form['id']}'" : "";
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
        $form_markup .= "<form $form_id action='./' method='POST' class='{$form_class}uk-grid-small' uk-grid>";

            foreach($form_fields as $key => $field) {
                // field vars
                $type           = !empty($field["type"]) ? $field["type"] : "text";
                $name           = !empty($field["name"]) ? $field["name"] : str_replace(" ", "_", $key);
                $label          = !empty($field["label"]) ? $field["label"] : $key;
                $placeholder    = !empty($field["placeholder"]) ? $field["placeholder"] : "";
                $required       = (!empty($field["required"]) && $field["required"] == true) ? true : false;
                $width          = !empty($field["width"]) ? $field["width"] : "1-1";
                $rows           = !empty($field["rows"]) ? $field["rows"] : "5";

                // required sttribute
                $required_attr = ($required == true) ? "required" : "";


                $form_markup .= "<div class='uk-width-$width@m'><div>";

                    if ($field["type"] == "text") {
                        $form_markup .= "
                            <label class='uk-form-label'>$label</label>
                            <input class='uk-input' type='text' name='$name' placeholder='$placeholder' $required_attr />
                        ";
                    } elseif ($field["type"] == "email") {
                        $form_markup .= "
                            <label class='uk-form-label'>$label</label>
                            <input class='uk-input' type='email' name='$name' placeholder='$placeholder' $required_attr />
                        ";
                    } elseif ($field["type"] == "textarea") {
                        $form_markup .= "
                            <label class='uk-form-label'>$label</label>
                            <textarea class='uk-textarea' rows='$rows' name='$name'  placeholder='$placeholder' $required_attr></textarea>
                        ";
                    }

                $form_markup .= "</div></div>";
            }

            // captcha
            $form_markup .= "
                <div class='uk-margin uk-grid-collapse' uk-grid>
                    <div class='uk-width-auto uk-flex uk-flex-middle'>
                        <label class='uk-h3'>$numb_q</label>
                    </div>
                    <div class='uk-width-auto'>
                        <input class='numb-captcha-answer uk-hidden' type='text' name='captcha_answer' value='$answer' required />
                        <input class='numb-captcha-q uk-input uk-form-width-xsmall uk-margin-small-left uk-text-center' type='text' name='numb_captcha' placeholder='?' required />
                    </div>
                </div>
            ";
            // submit button
            $form_markup .= "
                <div class='uk-margin-top'>
                    <input type='submit' name='$button_name' class='{$button_class}uk-button uk-button-$button_style' value='$button_text' />
                </div>
            ";
        $form_markup .= $this->session->CSRF->renderInput() . "</form>";

        return $form_markup;

    }


}
