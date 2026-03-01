<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CategoryController extends AbstractController
{
    #[Route('/category/searchcategory', name: 'category_search')]
    public function search(Request $request, CategoryRepository $categoryRepository): Response
    {
        $name = $request->query->get('name');
        $found = null;

        if ($name) {
            $found = $categoryRepository->findOneBy(['name' => $name]);
        }

        return $this->render('category/searchcategory.html.twig', [
            'found' => $found,
            'name' => $name,
        ]);
    }
}