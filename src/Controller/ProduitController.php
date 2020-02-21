<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Entity\Produit;
use App\Form\PanierAddType;
use App\Form\ProduitType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\CssSelector\XPath\TranslatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProduitController extends AbstractController
{
    /**
     * @Route("/produits", name="produits")
     */
    public function index(Request $request,  TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();

        $produits = new Produit();
        $form = $this->createForm(ProduitType::class, $produits);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){

            $fichier = $form->get('photoUpload')->getData();
            $nomFichier = uniqid().'.'.$fichier->guessExtension();

            try{
                $fichier->move(
                    $this->getParameter('upload_dir'),
                    $nomFichier
                );
            }
            catch(FileException $e){
                $this->addFlash('danger', $translator->trans('produit.file'));
                echo  'fichier add';
            }

            $produits->setPhoto($nomFichier);

            $em->persist($produits);
            $em->flush();

            $this->addFlash('success',  $translator->trans('produit.added'));
        }


        $utilisateurs = $em->getRepository(Produit::class)->findAll();

        return $this->render('produit/index.html.twig', [
            'produits' => $utilisateurs,
            'ajout_produit' => $form->createView()
        ]);
    }

    /**
     * @Route("/produits/edit/{id}", name="produit_edit")
     */
    public function edit(Request $request, Produit $produit=null, Panier $panier=null, TranslatorInterface $translator){

        if($produit != null){
            
            $form = $this->createForm(ProduitType::class, $produit);

            $affiche = $produit->getPhoto();

            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid()){
                $em = $this->getDoctrine()->getManager();

                $fichier = $form->get('photoUpload')->getData();

                if($fichier){
                    if($affiche != null){
                        unlink($this->getParameter('upload_dir').$affiche);
                    }

                    $nomFichier = uniqid().'.'.$fichier->guessExtension();

                    try{
                        $fichier->move(
                            $this->getParameter('upload_dir'),
                            $nomFichier
                        );
                    }
                    catch(FileException $e){
                        $this->addFlash('danger',  $translator->trans('produit.filedelete'));
                        echo  $translator->trans('produit.filedelete');
                    }

                    $produit->setPhoto($nomFichier);
                }

                $em->persist($produit);
                $em->flush();

                $this->addFlash('success',  $translator->trans('produit.fileupdate'));
            }
            $form2 = $this->createForm(PanierAddType::class, $panier);
            $form2->handleRequest($request);
            if($form2->isSubmitted() && $form2->isValid()){
                $em = $this->getDoctrine()->getManager();
                $em->persist($panier);
                $em->flush();

                $this->addFlash('success',  $translator->trans('produit.addpanier'));
            }
            return $this->render('produit/edit.html.twig', [
                'produit' => $produit,
                'ajout_panier' => $form2->createView()
            ]);

        }
        else{
            $this->addFlash('danger',  $translator->trans('produit.introuvable'));
            return $this->redirectToRoute('produits');
        }
    }

     /**
     * @Route("/delete/{id}", name="produit_delete")
     */
    public function delete(Produit $produit=null, TranslatorInterface $translator){

        if($produit != null){
            $em = $this->getDoctrine()->getManager();
            $em->remove($produit);
            $em->flush();

            $this->addFlash('warning',  $translator->trans('produit.deleteproduit'));
        }   
        else{
            $this->addFlash('danger', $translator->trans('produit.deleteproduit'));
        }

        return $this->redirectToRoute('produits');

    }
}
