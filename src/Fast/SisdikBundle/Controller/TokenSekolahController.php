<?php
namespace Fast\SisdikBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\TokenSekolah;
use Fast\SisdikBundle\Entity\Sekolah;

/**
 * @Route("/token-sekolah")
 */
class TokenSekolahController extends Controller
{
    /**
     * @Route("/", name="token-sekolah")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_cari_sekolah');

        $querybuilder = $em->createQueryBuilder()
            ->select('t')
            ->from('FastSisdikBundle:TokenSekolah', 't')
            ->leftJoin('t.sekolah', 't2')
            ->orderBy('t2.nama', 'ASC')
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['sekolah'] != '') {
                $querybuilder->where('t.sekolah = :sekolah');
                $querybuilder->setParameter("sekolah", $searchdata['sekolah']);
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
        ];
    }

    /**
     * @Route("/", name="token-sekolah_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:TokenSekolah:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new TokenSekolah();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity->setMesinProxy($entity->generateRandomToken());
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('token-sekolah_show', array('id' => $entity->getId())));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @param TokenSekolah $entity
     *
     * @return \Symfony\Component\Form\Form
     */
    private function createCreateForm(TokenSekolah $entity)
    {
        $form = $this->createForm('sisdik_tokensekolah', $entity, [
            'action' => $this->generateUrl('token-sekolah_create'),
            'method' => 'POST',
        ]);

        return $form;
    }

    /**
     * @Route("/new", name="token-sekolah_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new TokenSekolah();
        $form   = $this->createCreateForm($entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="token-sekolah_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:TokenSekolah')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity TokenSekolah tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="token-sekolah_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:TokenSekolah')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity TokenSekolah tak ditemukan.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @param TokenSekolah $entity
     *
     * @return \Symfony\Component\Form\Form
     */
    private function createEditForm(TokenSekolah $entity)
    {
        $form = $this->createForm('sisdik_tokensekolah', $entity, [
            'action' => $this->generateUrl('token-sekolah_update', [
                'id' => $entity->getId()
            ]),
            'method' => 'PUT',
        ]);

        return $form;
    }

    /**
     * @Route("/{id}", name="token-sekolah_update")
     * @Method("PUT")
     * @Template("FastSisdikBundle:TokenSekolah:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:TokenSekolah')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity TokenSekolah tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return
                $this->redirect($this->generateUrl('token-sekolah_edit', [
                    'id' => $id,
                ])
            );
        }

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="token-sekolah_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:TokenSekolah')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity TokenSekolah tak ditemukan.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('token-sekolah'));
    }

    /**
     * @param mixed $id
     *
     * @return \Symfony\Component\Form\Form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('token-sekolah_delete', [
                'id' => $id,
            ]))
            ->setMethod('DELETE')
            ->add('submit', 'submit', [
                'label' => 'label.delete',
                'attr' => [
                    'class' => 'btn alternative icon danger remove',
                ],
            ])
            ->getForm()
        ;
    }

    private function setCurrentMenu()
    {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu[$this->get('translator')->trans('headings.pengaturan.sisdik', [], 'navigations')][$this->get('translator')->trans('links.token.sekolah', [], 'navigations')]->setCurrent(true);
    }
}