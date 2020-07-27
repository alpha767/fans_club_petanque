<?php

use MailPoetVendor\Twig\Environment;
use MailPoetVendor\Twig\Error\LoaderError;
use MailPoetVendor\Twig\Error\RuntimeError;
use MailPoetVendor\Twig\Extension\SandboxExtension;
use MailPoetVendor\Twig\Markup;
use MailPoetVendor\Twig\Sandbox\SecurityError;
use MailPoetVendor\Twig\Sandbox\SecurityNotAllowedTagError;
use MailPoetVendor\Twig\Sandbox\SecurityNotAllowedFilterError;
use MailPoetVendor\Twig\Sandbox\SecurityNotAllowedFunctionError;
use MailPoetVendor\Twig\Source;
use MailPoetVendor\Twig\Template;

/* form/front_end_form.html */
class __TwigTemplate_99360f6c16396273a9e3356264be4c0e0a6e0a812bd72af75dd3c79ef7b21390 extends \MailPoetVendor\Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'content' => [$this, 'block_content'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        $this->displayBlock('content', $context, $blocks);
    }

    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 2
        echo "  ";
        if (($context["before_widget"] ?? null)) {
            // line 3
            echo "    ";
            echo ($context["before_widget"] ?? null);
            echo "
  ";
        }
        // line 5
        echo "
  ";
        // line 6
        if (($context["title"] ?? null)) {
            // line 7
            echo "    ";
            echo ($context["before_title"] ?? null);
            echo ($context["title"] ?? null);
            echo ($context["after_title"] ?? null);
            echo "
  ";
        }
        // line 9
        echo "
  <div class=\"mailpoet_form_popup_overlay\"></div>
  <div
    id=\"";
        // line 12
        echo \MailPoetVendor\twig_escape_filter($this->env, ($context["form_html_id"] ?? null), "html", null, true);
        echo "\"
    class=\"mailpoet_form mailpoet_form_";
        // line 13
        echo \MailPoetVendor\twig_escape_filter($this->env, ($context["form_type"] ?? null), "html", null, true);
        echo "\"
    ";
        // line 14
        if (($context["is_preview"] ?? null)) {
            // line 15
            echo "      data-is-preview=\"1\"
      data-editor-url=\"";
            // line 16
            echo \MailPoetVendor\twig_escape_filter($this->env, ($context["editor_url"] ?? null), "html", null, true);
            echo "\"
    ";
        }
        // line 18
        echo "  >
    ";
        // line 19
        if ((((($context["form_type"] ?? null) == "popup") || (($context["form_type"] ?? null) == "fixed_bar")) || (($context["form_type"] ?? null) == "slide_in"))) {
            // line 20
            echo "      <img
        class=\"mailpoet_form_close_icon\"
        alt=\"close\"
        width=20
        height=20
        src='";
            // line 25
            echo $this->extensions['MailPoet\Twig\Assets']->generateImageUrl((("form_close_icon/" . ($context["close_button_icon"] ?? null)) . ".svg"));
            echo "'
      >
    ";
        }
        // line 28
        echo "    ";
        echo ($context["styles"] ?? null);
        echo "
    <form
      target=\"_self\"
      method=\"post\"
      action=\"";
        // line 32
        echo admin_url("admin-post.php?action=mailpoet_subscription_form");
        echo "\"
      class=\"mailpoet_form mailpoet_form_form mailpoet_form_";
        // line 33
        echo \MailPoetVendor\twig_escape_filter($this->env, ($context["form_type"] ?? null), "html", null, true);
        echo "\"
      novalidate
      data-delay=\"";
        // line 35
        echo \MailPoetVendor\twig_escape_filter($this->env, ($context["delay"] ?? null), "html", null, true);
        echo "\"
      data-position=\"";
        // line 36
        echo \MailPoetVendor\twig_escape_filter($this->env, ($context["position"] ?? null), "html", null, true);
        echo "\"
      data-background-color=\"";
        // line 37
        echo \MailPoetVendor\twig_escape_filter($this->env, ($context["backgroundColor"] ?? null), "html", null, true);
        echo "\"
      data-font-family=\"";
        // line 38
        echo \MailPoetVendor\twig_escape_filter($this->env, ($context["fontFamily"] ?? null), "html", null, true);
        echo "\"
    >
      <input type=\"hidden\" name=\"data[form_id]\" value=\"";
        // line 40
        echo \MailPoetVendor\twig_escape_filter($this->env, ($context["form_id"] ?? null), "html", null, true);
        echo "\" />
      <input type=\"hidden\" name=\"token\" value=\"";
        // line 41
        echo \MailPoetVendor\twig_escape_filter($this->env, ($context["token"] ?? null), "html", null, true);
        echo "\" />
      <input type=\"hidden\" name=\"api_version\" value=\"";
        // line 42
        echo \MailPoetVendor\twig_escape_filter($this->env, ($context["api_version"] ?? null), "html", null, true);
        echo "\" />
      <input type=\"hidden\" name=\"endpoint\" value=\"subscribers\" />
      <input type=\"hidden\" name=\"mailpoet_method\" value=\"subscribe\" />

      ";
        // line 46
        echo ($context["html"] ?? null);
        echo "
      <div class=\"mailpoet_message\">
        <p class=\"mailpoet_validate_success\"
        ";
        // line 49
        if ( !($context["success"] ?? null)) {
            // line 50
            echo "        style=\"display:none;\"
        ";
        }
        // line 52
        echo "        >";
        echo \MailPoetVendor\twig_escape_filter($this->env, ($context["form_success_message"] ?? null), "html", null, true);
        echo "
        </p>
        <p class=\"mailpoet_validate_error\"
        ";
        // line 55
        if ( !($context["error"] ?? null)) {
            // line 56
            echo "        style=\"display:none;\"
        ";
        }
        // line 58
        echo "        >";
        if (($context["error"] ?? null)) {
            // line 59
            echo "        ";
            echo $this->extensions['MailPoet\Twig\I18n']->translate("An error occurred, make sure you have filled all the required fields.");
            echo "
        ";
        }
        // line 61
        echo "        </p>
      </div>
    </form>
  </div>

  ";
        // line 66
        if (($context["after_widget"] ?? null)) {
            // line 67
            echo "    ";
            echo ($context["after_widget"] ?? null);
            echo "
  ";
        }
    }

    public function getTemplateName()
    {
        return "form/front_end_form.html";
    }

    public function getDebugInfo()
    {
        return array (  200 => 67,  198 => 66,  191 => 61,  185 => 59,  182 => 58,  178 => 56,  176 => 55,  169 => 52,  165 => 50,  163 => 49,  157 => 46,  150 => 42,  146 => 41,  142 => 40,  137 => 38,  133 => 37,  129 => 36,  125 => 35,  120 => 33,  116 => 32,  108 => 28,  102 => 25,  95 => 20,  93 => 19,  90 => 18,  85 => 16,  82 => 15,  80 => 14,  76 => 13,  72 => 12,  67 => 9,  59 => 7,  57 => 6,  54 => 5,  48 => 3,  45 => 2,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "form/front_end_form.html", "C:\\petanque\\fans-club\\wp-content\\plugins\\mailpoet\\views\\form\\front_end_form.html");
    }
}
