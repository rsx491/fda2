<?php

/* {# inline_template_start #}<div class="grid-item-info">
<div class="grid-item-info-left" title="{{ filename }}">{{ filename }}</div>
<div class="grid-item-info-right">{{ filesize }}</div>
</div> */
class __TwigTemplate_9ddfa44d9a4a8115e8b4a2aab40051e5473211a111baebf48bf7bef197a5c2b4 extends Twig_Template
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
        $filters = array();
        $functions = array();

        try {
            $this->env->getExtension('sandbox')->checkSecurity(
                array(),
                array(),
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

        // line 1
        echo "<div class=\"grid-item-info\">
<div class=\"grid-item-info-left\" title=\"";
        // line 2
        echo $this->env->getExtension('sandbox')->ensureToStringAllowed($this->env->getExtension('drupal_core')->escapeFilter($this->env, (isset($context["filename"]) ? $context["filename"] : null), "html", null, true));
        echo "\">";
        echo $this->env->getExtension('sandbox')->ensureToStringAllowed($this->env->getExtension('drupal_core')->escapeFilter($this->env, (isset($context["filename"]) ? $context["filename"] : null), "html", null, true));
        echo "</div>
<div class=\"grid-item-info-right\">";
        // line 3
        echo $this->env->getExtension('sandbox')->ensureToStringAllowed($this->env->getExtension('drupal_core')->escapeFilter($this->env, (isset($context["filesize"]) ? $context["filesize"] : null), "html", null, true));
        echo "</div>
</div>";
    }

    public function getTemplateName()
    {
        return "{# inline_template_start #}<div class=\"grid-item-info\">
<div class=\"grid-item-info-left\" title=\"{{ filename }}\">{{ filename }}</div>
<div class=\"grid-item-info-right\">{{ filesize }}</div>
</div>";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  55 => 3,  49 => 2,  46 => 1,);
    }
}
/* {# inline_template_start #}<div class="grid-item-info">*/
/* <div class="grid-item-info-left" title="{{ filename }}">{{ filename }}</div>*/
/* <div class="grid-item-info-right">{{ filesize }}</div>*/
/* </div>*/
