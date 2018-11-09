<?php

namespace App\Twig;

use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    private $publicDir;

    public function __construct(KernelInterface $kernel)
    {
        $this->publicDir = $kernel->getContainer()->getParameter('kernel.project_dir') . '/public';
    }

    public function getFilters()
    {
        return array(
            new TwigFilter('file_hash', array($this, 'fileHash')),
        );
    }

    public function fileHash($path)
    {
        return $path . '?v=' . md5_file($this->publicDir . $path);
    }
}