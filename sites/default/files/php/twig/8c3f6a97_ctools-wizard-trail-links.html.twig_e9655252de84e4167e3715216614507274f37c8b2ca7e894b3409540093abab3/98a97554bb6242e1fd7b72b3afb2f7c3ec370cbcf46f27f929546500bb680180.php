<?php

/* modules/ctools/templates/ctools-wizard-trail-links.html.twig */
class __TwigTemplate_bc4255c274156a0883298624b4794c6dafb72137479be12bb96b4502c62ab08f extends Twig_Template
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
        $tags = array("if" => 1, "for" => 3);
        $filters = array("last" => 9);
        $functions = array("link" => 5);

        try {
            $this->env->getExtension('sandbox')->checkSecurity(
                array('if', 'for'),
                array('last'),
                array('link')
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
        if ((isset($context["trail"]) ? $context["trail"] : null)) {
            // line 2
            echo "<div class=\"wizard-trail\">
    ";
            // line 3
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((isset($context["trail"]) ? $context["trail"] : null));
            foreach ($context['_seq'] as $context["key"] => $context["value"]) {
                // line 4
                echo "        ";
                if (($context["key"] === (isset($context["step"]) ? $context["step"] : null))) {
                    // line 5
                    echo "            <strong>";
                    echo $this->env->getExtension('sandbox')->ensureToStringAllowed($this->env->getExtension('drupal_core')->escapeFilter($this->env, $this->env->getExtension('drupal_core')->getLink($this->getAttribute($context["value"], "title", array()), $this->getAttribute($context["value"], "url", array())), "html", null, true));
                    echo "</strong>
        ";
                } else {
                    // line 7
                    echo "            ";
                    echo $this->env->getExtension('sandbox')->ensureToStringAllowed($this->env->getExtension('drupal_core')->escapeFilter($this->env, $this->env->getExtension('drupal_core')->getLink($this->getAttribute($context["value"], "title", array()), $this->getAttribute($context["value"], "url", array())), "html", null, true));
                    echo "
        ";
                }
                // line 9
                echo "        ";
                if ( !($context["value"] === twig_last($this->env, (isset($context["trail"]) ? $context["trail"] : null)))) {
                    // line 10
                    echo "            ";
                    echo $this->env->getExtension('sandbox')->ensureToStringAllowed($this->env->getExtension('drupal_core')->escapeFilter($this->env, (isset($context["divider"]) ? $context["divider"] : null), "html", null, true));
                    echo "
        ";
                }
                // line 12
                echo "    ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['key'], $context['value'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 13
            echo "</div>
";
        }
    }

    public function getTemplateName()
    {
        return "modules/ctools/templates/ctools-wizard-trail-links.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  82 => 13,  76 => 12,  70 => 10,  67 => 9,  61 => 7,  55 => 5,  52 => 4,  48 => 3,  45 => 2,  43 => 1,);
    }
}
/* {% if trail %}*/
/* <div class="wizard-trail">*/
/*     {% for key, value in trail %}*/
/*         {% if key is same as(step) %}*/
/*             <strong>{{ link(value.title, value.url) }}</strong>*/
/*         {% else %}*/
/*             {{ link(value.title, value.url) }}*/
/*         {% endif %}*/
/*         {% if value is not same as(trail|last) %}*/
/*             {{ divider }}*/
/*         {% endif %}*/
/*     {% endfor %}*/
/* </div>*/
/* {% endif %}*/
/* */
