<?php

/* modules/entity_browser/templates/html--entity-browser--iframe.html.twig */
class __TwigTemplate_8d602c078e8da333df44068cb0603ab4ad1e87d11ca309e8360bef931f1249ff extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $tags = array();
        $filters = array("raw" => 35, "safe_join" => 36, "t" => 42);
        $functions = array();

        try {
            $this->env->getExtension('sandbox')->checkSecurity(
                array(),
                array('raw', 'safe_join', 't'),
                array()
            );
        } catch (Twig_Sandbox_SecurityError $e) {
            $e->setTemplateFile($this->getTemplateName());

            if ($e instanceof Twig_Sandbox_SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof Twig_Sandbox_SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof Twig_Sandbox_SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

        // line 32
        echo "<!DOCTYPE html>
<html";
        // line 33
        echo $this->env->getExtension('sandbox')->ensureToStringAllowed($this->env->getExtension('drupal_core')->escapeFilter($this->env, (isset($context["html_attributes"]) ? $context["html_attributes"] : null), "html", null, true));
        echo ">
<head>
    <head-placeholder token=\"";
        // line 35
        echo $this->env->getExtension('sandbox')->ensureToStringAllowed($this->env->getExtension('drupal_core')->renderVar((isset($context["placeholder_token"]) ? $context["placeholder_token"] : null)));
        echo "\">
    <title>";
        // line 36
        echo $this->env->getExtension('sandbox')->ensureToStringAllowed($this->env->getExtension('drupal_core')->renderVar($this->env->getExtension('drupal_core')->safeJoin($this->env, (isset($context["head_title"]) ? $context["head_title"] : null), " | ")));
        echo "</title>
    <css-placeholder token=\"";
        // line 37
        echo $this->env->getExtension('sandbox')->ensureToStringAllowed($this->env->getExtension('drupal_core')->renderVar((isset($context["placeholder_token"]) ? $context["placeholder_token"] : null)));
        echo "\">
    <js-placeholder token=\"";
        // line 38
        echo $this->env->getExtension('sandbox')->ensureToStringAllowed($this->env->getExtension('drupal_core')->renderVar((isset($context["placeholder_token"]) ? $context["placeholder_token"] : null)));
        echo "\">
</head>
<body";
        // line 40
        echo $this->env->getExtension('sandbox')->ensureToStringAllowed($this->env->getExtension('drupal_core')->escapeFilter($this->env, (isset($context["attributes"]) ? $context["attributes"] : null), "html", null, true));
        echo ">
<a href=\"#main-content\" class=\"visually-hidden focusable\">
    ";
        // line 42
        echo $this->env->getExtension('sandbox')->ensureToStringAllowed($this->env->getExtension('drupal_core')->renderVar(t("Skip to main content")));
        echo "
</a>
  ";
        // line 44
        echo $this->env->getExtension('sandbox')->ensureToStringAllowed($this->env->getExtension('drupal_core')->escapeFilter($this->env, (isset($context["page"]) ? $context["page"] : null), "html", null, true));
        echo "
  <js-bottom-placeholder token=\"";
        // line 45
        echo $this->env->getExtension('sandbox')->ensureToStringAllowed($this->env->getExtension('drupal_core')->renderVar((isset($context["placeholder_token"]) ? $context["placeholder_token"] : null)));
        echo "\">
</body>
</html>
";
    }

    public function getTemplateName()
    {
        return "modules/entity_browser/templates/html--entity-browser--iframe.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  82 => 45,  78 => 44,  73 => 42,  68 => 40,  63 => 38,  59 => 37,  55 => 36,  51 => 35,  46 => 33,  43 => 32,);
    }
}
/* {#*/
/* /***/
/*  * @file*/
/*  * Theme override for the IFrame entity browser. Template copied from core/modules/system/templates/html.html.twig*/
/*  **/
/*  * Variables:*/
/*  * - logged_in: A flag indicating if user is logged in.*/
/*  * - root_path: The root path of the current page (e.g., node, admin, user).*/
/*  * - node_type: The content type for the current node, if the page is a node.*/
/*  * - css: A list of CSS files for the current page.*/
/*  * - head: Markup for the HEAD element (including meta tags, keyword tags, and*/
/*  *   so on).*/
/*  * - head_title: List of text elements that make up the head_title variable.*/
/*  *   May contain or more of the following:*/
/*  *   - title: The title of the page.*/
/*  *   - name: The name of the site.*/
/*  *   - slogan: The slogan of the site.*/
/*  * - page_top: Initial rendered markup. This should be printed before 'page'.*/
/*  * - page: The rendered page markup.*/
/*  * - page_bottom: Closing rendered markup. This variable should be printed after*/
/*  *   'page'.*/
/*  * - styles: Style tags necessary to import all necessary CSS files in the head.*/
/*  * - scripts: Script tags necessary to load the JavaScript files and settings*/
/*  *   in the head.*/
/*  * - db_offline: A flag indicating if the database is offline.*/
/*  **/
/*  * @see template_preprocess_html()*/
/*  **/
/*  * @ingroup themeable*/
/*  *//* */
/* #}*/
/* <!DOCTYPE html>*/
/* <html{{ html_attributes }}>*/
/* <head>*/
/*     <head-placeholder token="{{ placeholder_token|raw }}">*/
/*     <title>{{ head_title|safe_join(' | ') }}</title>*/
/*     <css-placeholder token="{{ placeholder_token|raw }}">*/
/*     <js-placeholder token="{{ placeholder_token|raw }}">*/
/* </head>*/
/* <body{{ attributes }}>*/
/* <a href="#main-content" class="visually-hidden focusable">*/
/*     {{ 'Skip to main content'|t }}*/
/* </a>*/
/*   {{ page }}*/
/*   <js-bottom-placeholder token="{{ placeholder_token|raw }}">*/
/* </body>*/
/* </html>*/
/* */
