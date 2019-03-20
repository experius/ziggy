<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius\Util\Console\Helper;

use Exception;
use Experius\Util\Template\Twig;
use RuntimeException;
use Symfony\Component\Console\Helper\Helper;

/**
 * Class TwigHelper
 *
 * @package Experius\Util\Console\Helper
 */
class TwigHelper extends Helper
{
    /**
     * @var \Experius\Util\Template\Twig
     */
    protected $twig;

    /**
     * @param array $baseDirs
     * @throws RuntimeException
     */
    public function __construct(array $baseDirs)
    {
        try {
            $this->twig = new Twig($baseDirs);
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * Renders a twig template file
     *
     * @param string $template
     * @param array $variables
     * @return mixed
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function render($template, $variables = array())
    {
        return $this->twig->render($template, $variables);
    }

    /**
     * Renders a twig string
     *
     * @param string $string
     * @param array $variables
     *
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function renderString($string, $variables = array())
    {
        return $this->twig->renderString($string, $variables);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'twig';
    }
}