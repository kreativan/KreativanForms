<?php
/**
 *  KreativanForms
 *
 *  @author Ivan Milincic <kreativan@outlook.com>
 *  @copyright 2020 kraetivan.net
 *  @link http://www.kraetivan.net
 *
 *
*/

class KreativanForms extends WireData implements Module {

  public static function getModuleInfo() {
    return array(
      'title' => 'Kreativan Forms',
      'version' => 100,
      'summary' => 'Create and process forms...',
      'icon' => 'wpforms',
      'singular' => true,
      'autoload' => false
    );
  }

  /* ----------------------------------------------------------------
    Actions
  ------------------------------------------------------------------- */

  /**
   *  Send Email
   *  @param string $submitName - name of the form submit button
   *  @param string $emailTo - email address to send submission to
   *  @param string $emailFrom - email address used as $mail->from()
   *  @param string $emailFromName - used for email from name
   *  @param string $emailField - email field name to use for replyTo
   *  @param string $subject - subject field name or hardcoded string
   *  @param string $message - Success message
   *  @param string $redirect_url - redirect url after form submit
   */
  public function sendEmail($options = []) {

    $submitName = !empty($options["submitName"]) ? $options["submitName"] : "submit";
    $emailTo = !empty($options["emailTo"]) ? $options["emailTo"] : "";
    $emailFrom = !empty($options["emailFrom"]) ? $options["emailFrom"] : "";
    $emailFromName = !empty($options["emailFromName"]) ? $options["emailFromName"] : "";
    $emailField = !empty($options["emailField"]) ? $options["emailField"] : "";
    $subject = !empty($options["subject"]) ? $options["subject"] : "";
    $redirect_url = !empty($options["redirect_url"]) ? $options["redirect_url"] : "./";
    $message = !empty($options["message"]) ? $options["message"] : __("Email has been sent");

    if($this->input->post->{$submitName}) {

      if($this->formValidate()) {

        $email_body = $this->getEmailBody($this->input->post, $submitName);
        $replyTo = $this->input->post->{$emailField};

        // send email
        $m = new wireMail();
        $m->to($emailTo);
        if($emailFrom != "") $m->from($emailFrom);
        if($emailFromName != "") $m->fromName($emailFromName);
        if($emailField != "") $m->replyTo($replyTo);
        $m->subject($subject);
        $m->bodyHTML($email_body);
        if($this->getAttachments()) {
          foreach($this->getAttachments() as $file) $m->attachment($file);
        }
        $m->send();

        // delete temp files if any
        $this->deleteTempFiles();

        // Set error messages
        $this->session->set("kreativan_forms_status", "success");
        $this->session->set("kreativan_forms_title", __("Success"));
        $this->session->set("kreativan_forms_message", $message);


      } else {

        // Set error messages
        $this->session->set("kreativan_forms_status", "error");
        $this->session->set("kreativan_forms_title", __("Error"));
        $this->session->set("kreativan_forms_message", __("Wrong Capcha!"));

      }

      // Reset token
      $this->session->CSRF->resetToken();

      if($redirect_url != "") $this->session->redirect($redirect_url);

    }

  }

  /* ----------------------------------------------------------------
    Methods
  ------------------------------------------------------------------- */

  public function formValidate() {
    if(!$this->session->CSRF->hasValidToken()) return false;
    if(!empty($this->input->post->honey_email)) return false;
    if($this->input->post->is_answer) {
      $your_answer = $this->sanitizer->text($this->input->post->your_answer);
      $is_answer = $this->sanitizer->text($this->input->post->is_answer);
      return ($your_answer == $is_answer) ? true : false;
    } else {
      return true;
    }
  }

  // Get Form Data
  // remove unnesesery stuff from $_POST
  public function getFormData($POST, $submit_name = "submit") {
    $arr = [];
    foreach($POST as $key => $value) {
      if($key != $submit_name && $key != "is" && $key != "is_answer" && $key != "your_answer") {
        $key = str_replace("_", " ", $key);
        $key = ucfirst($key);
        $arr[$key] = !is_array($value) ? htmlspecialchars($value) : $value;
      }
    }
    $POST = array_pop($arr);
    return $arr;
  }

  // Format Email Body
  // Get $_POST data and put it in a string
  public function getEmailBody($POST, $submit_name = "submit") {
    $arr = $this->getFormData($POST, $submit_name);
    $email_body = "";
    foreach($arr as $key => $value) {
      if($value != "") {
        if(!is_array($value)) {
          $email_body .= "<p><b>$key:</b><br /> $value</p>";
        } else {
          $options = "";
          $i = 0;
          foreach($value as $item) {
            $options .= ($i++ == 0) ? "$item" : ", $item";
          }
          $email_body .= "<p><b>$key:</b><br /> $options<br /></p>";
        }
      }
    }
    return $email_body;
  }

  // Get files from $_POST and $_FILES
  // Upload files and include them in array
  public function getAttachments() {
    $is_files = true;
    foreach($_FILES as $file) {
      if($file["error"] > 0) $is_files = false;
    }
    if($is_files === true) {
      $this->fileUpload();
      $files_arr = [];
      foreach($_FILES as $file) {
        $files_arr[] .= $this->config->paths->assets."kreativan-forms/{$file['name']}";
      }
      return $files_arr;
    } else {
      return false;
    }
  }

  // File Upload
  public function fileUpload() {
    $dest = $this->config->paths->assets . "kreativan-forms/";
    if(!file_exists($dest) && !is_dir($dest)) mkdir($dest);
    foreach($_FILES as $file) {
      $tempFile = $file["tmp_name"];
      $targetFile =  $dest. $file['name'];
      move_uploaded_file($tempFile,$targetFile);
    }
  }

  // Delete temp files
  public function deleteTempFiles() {
    $dest = $this->config->paths->assets . "kreativan-forms/";
    if(file_exists($dest)) {
      $this->files->rmdir($dest, true);
    }
  }

  /* ----------------------------------------------------------------
    FORM
  ------------------------------------------------------------------- */

  /**
   *  Render Form
   *  @param array $fields
   *  @param array $options
   */
  public function renderForm($fields = [], $options = []) {

    $action       = !empty($options["action"]) ? $options["action"] : "./";

    $labels       = !empty($options["labels"]) && $options["labels"] == "false" ? false : true;
    $captcha      = !empty($options["captcha"]) && $options["captcha"] == "false" ? false : $options["captcha"];

    $submit_name  = !empty($options["submit_name"]) ? $options["submit_name"] : "submit";
    $submit_title = !empty($options["submit_title"]) ? $options["submit_title"] : "Submit";
    $submit_style = !empty($options["submit_style"]) ? $options["submit_style"] : "primary";

    $css_class    = !empty($options["css_class"]) ? $options["css_class"] : "";
    $css_id       = !empty($options["css_id"]) ? $options["css_id"] : "kreativan-form-{$this->page->template}";
    $grid_size    = !empty($options["grid_size"]) ? $options["grid_size"] : "medium"; // small/medium/large
    $alert_type   = !empty($options["alert_type"]) ? $options["alert_type"] : $this->alert_type;

    // To load date flatpickr?
    $is_date = false;
    $is_time = false;

    $i = 0;

    // Form Start
    $form = "<form id='$css_id' action='$action' method='POST' enctype='multipart/form-data' class='uk-form-stacked $css_class'>";

      // include alert
      $form .= $this->alert($alert_type);

      // render fields
      $form .= "<div class='uk-grid uk-grid-$grid_size' uk-grid>";

        foreach($fields as $key => $value) {
          $x = $i++;
          $width = !empty($value['width']) ? $value['width'] : "1-1";
          $form .= "<div class='uk-width-{$width}@m uk-grid-margin'>";
          $form .= "<div class='form-row-field'>";
          // execute method based on field type
          if(method_exists($this, $value["type"])) {
            $form .= $this->{$value["type"]}($key, $value, $labels);
          }
          $form .= "</div></div>";
          // If its date/time set is date/time to true so we can load flatpicr script
          if($value["type"] == "date") $is_date = true;
          if($value["type"] == "time") $is_time = true;
        }

        // Captcha & submit button
        $form .= "<div class='form-row-submit uk-width-1-1'>";
          if($captcha && $captcha != "hcaptcha") {
            $form .= "<div class='uk-grid-small' uk-grid>";
              $form .= "<div class='uk-width-auto@s'>".$this->captcha()."</div>";
              $form .= "<div class='uk-width-expand@s'>";
              $form .= "<input class='uk-button uk-button-$submit_style' type='submit' name='$submit_name' value='$submit_title' />";
              $form .= "</div>";
            $form .= "</div>";
          } else {
            $form .= "<input class='uk-button uk-button-$submit_style' type='submit' name='$submit_name' value='$submit_title' />";
          }
        $form .= "</div>";

      $form .= "</div>";

      // honeypot
      $form .= $this->honeypot();

      // CSRF Token
      $form .= $this->session->CSRF->renderInput();

      

      // load flatpicr script
      if($is_date || $is_time) $form .= $this->flatpickr($css_id);

    // Form end
    $form .= "</form>";

    return $form ;

  }

  /* ----------------------------------------------------------------
    Fields
  ------------------------------------------------------------------- */

  //
  //  heading
  //
  public function heading($name = "", $arr = "") {

    $title = !empty($arr["title"]) ? $arr["title"] : "";
    $class = !empty($arr["class"]) ? $arr["class"] : "";

    if($title == "") return false;
    $html = "<div class='in-form-heading $class'><span>$title</span></div>";
    return $html;

  }

  //
  // text
  //
  public function text($name = "", $arr = [], $show_label = true) {

    if($name == "") return false;

    $label          = !empty($arr["label"]) ? $arr["label"] : $name;
    $value          = !empty($arr["value"]) ? $arr["value"] : "";
    $placeholder    = !empty($arr["placeholder"]) ? $arr["placeholder"] : "";
    $required       = (!empty($arr["required"]) && $arr["required"] == "true") ? "required" : "";

    $html = "";

    if($show_label === true) {
      $req = ($required != "") ? " <span class='required uk-text-danger'>*</span>" : "";
      $html .= "<label class='uk-form-label'>{$label}{$req}</label>";
    }

    $html .= "<input class='uk-input' type='text' name='$name' value='$value' placeholder='$placeholder' $required />";

    return $html;

  }

  //
  // Email
  //
  public function email($name = "", $arr = [], $show_label = true) {

    if($name == "") return false;

    $label          = !empty($arr["label"]) ? $arr["label"] : $name;
    $value          = !empty($arr["value"]) ? $arr["value"] : "";
    $placeholder    = !empty($arr["placeholder"]) ? $arr["placeholder"] : "";
    $required       = (!empty($arr["required"]) && $arr["required"] == "true") ? "required" : "";

    $html = "";

    if($show_label === true) {
      $req = ($required != "") ? " <span class='required uk-text-danger'>*</span>" : "";
      $html .= "<label class='uk-form-label'>{$label}{$req}</label>";
    }

    $html .= "<input class='uk-input' type='email' name='$name' value='$value' placeholder='$placeholder' $required />";

    return $html;

  }

  //
  // url
  //
  public function url($name = "", $arr = [], $show_label = true) {

    if($name == "") return false;

    $label          = !empty($arr["label"]) ? $arr["label"] : $name;
    $value          = !empty($arr["value"]) ? $arr["value"] : "";
    $placeholder    = !empty($arr["placeholder"]) ? $arr["placeholder"] : "";
    $required       = (!empty($arr["required"]) && $arr["required"] == "true") ? "required" : "";

    $html = "";

    if($show_label === true) {
      $req = ($required != "") ? " <span class='required uk-text-danger'>*</span>" : "";
      $html .= "<label class='uk-form-label'>{$label}{$req}</label>";
    }

    $html .= "<input class='uk-input' type='url' name='$name' value='$value' placeholder='$placeholder' $required />";

    return $html;

  }

  //
  // textarea
  //
  public function textarea($name = "", $arr = [], $show_label = true) {

    if($name == "") return false;

    $label          = !empty($arr["label"]) ? $arr["label"] : $name;
    $value          = !empty($arr["value"]) ? $arr["value"] : "";
    $placeholder    = !empty($arr["placeholder"]) ? $arr["placeholder"] : "";
    $required       = (!empty($arr["required"]) && $arr["required"] == "true") ? "required" : "";
    $rows           = !empty($arr["rows"]) ? $arr["rows"] : 10;

    $html = "";

    if($show_label === true) {
      $req = ($required != "") ? " <span class='required uk-text-danger'>*</span>" : "";
      $html .= "<label class='uk-form-label'>{$label}{$req}</label>";
    }

    $html .= "<textarea class='uk-textarea' name='$name' rows='$rows' placeholder='$placeholder' $required>$value</textarea>";

    return $html;

  }

  //
  // radio
  //
  public function radio($name = "", $arr = [], $show_label = true) {

    if($name == "") return false;

    $label          = !empty($arr["label"]) ? $arr["label"] : $name;
    $value          = !empty($arr["value"]) ? $arr["value"] : "";
    $required       = (!empty($arr["required"]) && $arr["required"] == "true") ? "required" : "";
    $options        = !empty($arr["options"]) ? $arr["options"] : "";

    $html = "";

    if($show_label === true) {
      $req = ($required != "") ? " <span class='required uk-text-danger'>*</span>" : "";
      $html .= "<label class='uk-form-label'>{$label}{$req}</label>";
    }

    $html .= "<div class='uk-flex'>";
    if($options != "" && count($options) > 0) {
      $i = 0;
      foreach($options as $item) {
        $checked = ($value == $item || $i++ == 0) ? "checked" : "";
        $html .= "<label class='uk-text-small uk-margin-right'>";
        $html .= "<input class='uk-radio' type='radio' name='{$name}[]' value='$item' $checked $required />";
        $html .= "<span style='margin-left:5px'>$item</span>";
        $html .= "</label>";
      }
    }
    $html .= "</div>";

    return $html;

  }

  //
  // checkbox
  // pass the array of options as value to preselect the checkboxes
  //
  public function checkbox($name = "", $arr = [], $show_label = true) {

    if($name == "") return false;

    $label          = !empty($arr["label"]) ? $arr["label"] : $name;
    $value          = !empty($arr["value"]) ? $arr["value"] : "";
    $required       = (!empty($arr["required"]) && $arr["required"] == "true") ? "required" : "";
    $options        = !empty($arr["options"]) ? $arr["options"] : "";

    $html = "";

    if($show_label === true) {
      $req = ($required != "") ? " <span class='required uk-text-danger'>*</span>" : "";
      $html .= "<label class='uk-form-label'>{$label}{$req}</label>";
    }

    $html .= "<div class='uk-flex'>";
    if($options != "" && count($options) > 0) {
      $i = 0;
      foreach($options as $item) {
        $checked = ($value != "" && count($value) > 0 && in_array($item, $value)) ? "checked" : "";
        $html .= "<label class='uk-text-small uk-margin-right'>";
        $html .= "<input class='uk-checkbox' type='checkbox' name='{$name}[]' value='$item' $checked $required />";
        $html .= "<span style='margin-left:5px'>$item</span>";
        $html .= "</label>";
      }
    }
    $html .= "</div>";

    return $html;

  }

  //
  // select
  //
  public function select($name = "", $arr = [], $show_label = true) {

    if($name == "") return false;

    $label          = !empty($arr["label"]) ? $arr["label"] : $name;
    $value          = !empty($arr["value"]) ? $arr["value"] : "";
    $required       = (!empty($arr["required"]) && $arr["required"] == "true") ? "required" : "";
    $options        = !empty($arr["options"]) ? $arr["options"] : "";
    $placeholder    = !empty($arr["placeholder"]) ? $arr["placeholder"] : __("Select");

    $html = "";

    if($show_label === true) {
      $req = ($required != "") ? " <span class='required uk-text-danger'>*</span>" : "";
      $html .= "<label class='uk-form-label'>{$label}{$req}</label>";
    }

    $html .= "<select class='uk-select' name='$name'>";
    $html .= "<option value=''>".$placeholder."</option>";
    if($options != "" && count($options) > 0) {
      foreach($options as $item) {
        $selected = ($value == $item) ? "selected" : "";
        $html .= "<option value='$item' $selected>$item</option>";
      }
    }
    $html .= "</select>";

    return $html;

  }

  //
  // file
  //
  public function file($name = "", $arr = [], $show_label = true) {

    if($name == "") return false;

    $label          = !empty($arr["label"]) ? $arr["label"] : $name;
    $required       = (!empty($arr["required"]) && $arr["required"] == "true") ? "required" : "";
    $placeholder    = !empty($arr["placeholder"]) ? $arr["placeholder"] : "";
    $accept         = !empty($arr["accept"]) ? $arr["accept"] : "";

    $html = "";

    if($show_label === true) {
      $req = ($required != "") ? " <span class='required uk-text-danger'>*</span>" : "";
      $html .= "<label class='uk-form-label'>{$label}{$req}</label>";
    }

    $html .= "<div class='uk-width-1-1' uk-form-custom='target: true'>";
    $html .= "<input type='file' name='$name' accept='$accept'>";
    $html .= "<input class='uk-input' type='text' placeholder='$placeholder' disabled>";
    $html .= "</div>";

    return $html;

  }

  //
  // date
  //
  public function date($name = "", $arr = [], $show_label = true) {

    if($name == "") return false;

    $label          = !empty($arr["label"]) ? $arr["label"] : $name;
    $value          = !empty($arr["value"]) ? $arr["value"] : "";
    $placeholder    = !empty($arr["placeholder"]) ? $arr["placeholder"] : "";
    $required       = (!empty($arr["required"]) && $arr["required"] == "true") ? "required" : "";

    $html = "";

    if($show_label === true) {
      $req = ($required != "") ? " <span class='required uk-text-danger'>*</span>" : "";
      $html .= "<label class='uk-form-label'>{$label}{$req}</label>";
    }

    $html .= "<input class='uk-input datePicker' type='text' name='$name' value='$value' placeholder='$placeholder' $required />";

    return $html;

  }

  //
  // time
  //
  public function time($name = "", $arr = [], $show_label = true) {

    if($name == "") return false;

    $label          = !empty($arr["label"]) ? $arr["label"] : $name;
    $value          = !empty($arr["value"]) ? $arr["value"] : "";
    $placeholder    = !empty($arr["placeholder"]) ? $arr["placeholder"] : "";
    $required       = (!empty($arr["required"]) && $arr["required"] == "true") ? "required" : "";

    $html = "";

    if($show_label === true) {
      $req = ($required != "") ? " <span class='required uk-text-danger'>*</span>" : "";
      $html .= "<label class='uk-form-label'>{$label}{$req}</label>";
    }

    $html .= "<input class='uk-input timePicker' type='text' name='$name' value='$value' placeholder='$placeholder' $required />";

    return $html;

  }

  //
  //  captcha
  //

  public function captcha() {
    $numb_1 = rand(1, 10);
    $numb_2 = rand(1, 10);
    $answer = $numb_1 + $numb_2;
    $q = "$numb_1 + $numb_2 = ";
    $html = "<div class='numb-captcha uk-grid-collapse' uk-grid>";
      $html .= "<div class='uk-width-auto uk-flex uk-flex-middle'><div class='uk-h3 uk-margin-remove'>$q</div></div>";
      $html .= "<div class='uk-width-auto'>";
        $html .= "<input class='uk-hidden' type='text' name='is_answer' value='$answer' required />";
        $html .= "<input class='uk-input uk-form-width-xsmall uk-margin-small-left uk-text-center' type='text' name='your_answer' placeholder='?' required />";
      $html .= "</div>";
    $html .= "</div>";
    return $html;
  }

  //
  //  Honeypot
  //

  public function honeypot() {
    return "<input class='uk-hidden' type='email' name='honey_email' />";
  }

  /* ----------------------------------------------------------------
    flatpickr
  ------------------------------------------------------------------- */
  /**
   *  Load and init flatpickr
   *  @param form_id string form css id
   */
  public function flatpickr($form_id) {

    $module_folder = $this->config->urls->siteModules . $this->className() . "/";

    // flatpickr files
    $cssFile = $module_folder . "flatpickr/flatpickr.min.css";
    $jsFile = $module_folder . "flatpickr/flatpickr.min.js";

    // only works with module autoload
    // $this->config->styles->add($cssFile);
    // $this->config->scripts->add($jsFile);

    $out = "";

    $out = "<link rel='stylesheet' type='text/css' href='$cssFile'>";
    $out .= "<script type='text/javascript' src='$jsFile'></script>";

    // flatpickr lang files
    $locale = $this->user->language->name != "default" ? strtolower($this->user->language->name) : "en";
    $locale = ($locale == "rs") ? "sr" : $locale;
    if($locale != "en") {
      $locale_file = $module_folder . "flatpickr/l10n/" . $locale . ".js";
      // only works with module autoload
      // $this->config->scripts->add($locale_file);
      $out .= "<script type='text/javascript' src='$locale_file'></script>";
    }

    $out .= "
      <script>
        document.addEventListener('DOMContentLoaded', function(){
          var dateFields = document.querySelectorAll('#{$form_id} .datePicker');
          var timeFields = document.querySelectorAll('#{$form_id} .timePicker');

          // set locale globaly - use 'locale': $locale - to set it per instance
          // flatpickr.localize(flatpickr.l10ns.{$locale});

          // init date pickers
          dateFields.forEach(e => {
            e.flatpickr({
              'locale': '{$locale}',
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

    return $out;

  }

  /* ----------------------------------------------------------------
    Alert
  ------------------------------------------------------------------- */
  // Alert
  // Display session based alert message
  public function alert($alert_type = "") {
    if($this->session->get("kreativan_forms_status")) {

      $alert_type = !empty($alert_type) ? $alert_type : $this->alert_type;
      $status = $this->session->get("kreativan_forms_status");
      $status = ($status == "error") ? "danger" : $status;
      $message = $this->session->get("kreativan_forms_message");
      $title = $this->session->get("kreativan_forms_title");

      // return
      $alert = "";

      if($alert_type == "alert") {
        $alert .= "
          <div class='uk-alert uk-alert-$status'>
            <a class='uk-alert-close' uk-close></a>
            <p>{$message}</p>
          </div>"
        ;
      } elseif($alert_type == "dialog") {
        $alert = "
          <script>
            window.addEventListener('DOMContentLoaded', function() {
              UIkit.modal.alert(`<div class='modal-dialog-headline uk-h4 uk-text-center '>${message}</div>`);
            });
          </script>
        ";
      } elseif($alert_type == "notification") {
        $alert .= "
          <script>
            window.addEventListener('DOMContentLoaded', function() {
              UIkit.notification({status:'{$status}' ,message: '{$message}', pos: 'top-center', timeout: '3000'})
            }); 
          </script>
        ";
      }

      $this->session->remove("kreativan_forms_status");
      $this->session->remove("kreativan_forms_title");
      $this->session->remove("kreativan_forms_message");

      return $alert;

    }
  }

}
