<?php

namespace CCR\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AppKernelExplorerController extends BaseController
{
    #[Route('/appkernel/index.php')]
    public function index(Request $request): Response
    {
        return $this->render('twig/app_kernel.html.twig', [

        ]);
    }
}
