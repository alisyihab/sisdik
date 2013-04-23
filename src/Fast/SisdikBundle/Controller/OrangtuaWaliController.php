<?php

namespace Fast\SisdikBundle\Controller;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\OrangtuaWali;
use Fast\SisdikBundle\Form\OrangtuaWaliType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * OrangtuaWali controller.
 *
 * @Route("/{sid}/ortuwali", requirements={"sid"="\d+"})
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_WALI_KELAS', 'ROLE_PANITIA_PSB')")
 */
class OrangtuaWaliController extends Controller
{

    /**
     * Menyetel rute untuk kembali ke
     * info siswa atau pendaftar berdasarkan path
     */
    private function ambilRuteAsal() {
        $path = $this->getRequest()->getPathInfo();

        if (strpos($path, 'pendaftar')) {
            $rute = "pendaftar";
        } else if (strpos($path, "siswa")) {
            $rute = "siswa";
        } else {
            $rute = "";
        }

        return $rute;
    }

    /**
     * Lists all OrangtuaWali entities.
     *
     * @Route("/pendaftar", name="ortuwali-pendaftar")
     * @Route("/siswa", name="ortuwali-siswa")
     * @Template()
     */
    public function indexAction($sid) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:OrangtuaWali', 't')
                ->where('t.siswa = :siswa')->orderBy('t.aktif', 'DESC')->setParameter('siswa', $sid);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->get('request')->query->get('page', 1));

        return array(
                'pagination' => $pagination,
                'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid),
                'ruteasal' => $this->ambilRuteAsal(),
        );
    }

    /**
     * Mengaktifkan status orang tua wali, dan menonaktifkan yang lain
     *
     * @Route("/pendaftar/{id}/activate", name="ortuwali-pendaftar_activate")
     * @Route("/siswa/{id}/activate", name="ortuwali-siswa_activate")
     */
    public function activateAction($sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity OrangtuaWali tak ditemukan.');
        }

        $query = $em->createQueryBuilder()->update('FastSisdikBundle:OrangtuaWali', 't')->set('t.aktif', 0)
                ->where('t.siswa = :siswa')->setParameter('siswa', $sid)->getQuery();
        $query->execute();

        $entity->setAktif(true);
        $em->persist($entity);
        $em->flush();

        return $this
                ->redirect(
                        $this
                                ->generateUrl(
                                        $this->ambilRuteAsal() == 'pendaftar' ? 'ortuwali-pendaftar'
                                                : 'ortuwali-siswa',
                                        array(
                                            'sid' => $sid
                                        )));
    }

    /**
     * Finds and displays a OrangtuaWali entity.
     *
     * @Route("/pendaftar/{id}/show", name="ortuwali-pendaftar_show")
     * @Route("/siswa/{id}/show", name="ortuwali-siswa_show")
     * @Template()
     */
    public function showAction($sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity OrangtuaWali tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'delete_form' => $deleteForm->createView(),
                'ruteasal' => $this->ambilRuteAsal(),
        );
    }

    /**
     * Displays a form to create a new OrangtuaWali entity.
     *
     * @Route("/pendaftar/new", name="ortuwali-pendaftar_new")
     * @Route("/siswa/new", name="ortuwali-siswa_new")
     * @Template()
     */
    public function newAction($sid) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new OrangtuaWali();
        $form = $this->createForm(new OrangtuaWaliType(), $entity);

        return array(
                'entity' => $entity, 'form' => $form->createView(),
                'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid),
                'ruteasal' => $this->ambilRuteAsal(),
        );
    }

    /**
     * Creates a new OrangtuaWali entity.
     *
     * @Route("/pendaftar/create", name="ortuwali-pendaftar_create")
     * @Route("/siswa/create", name="ortuwali-siswa_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:OrangtuaWali:new.html.twig")
     */
    public function createAction(Request $request, $sid) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new OrangtuaWali();
        $form = $this->createForm(new OrangtuaWaliType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($sid);
            $entity->setSiswa($siswa);
            $entity->setAktif(false);

            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.ortuwali.tersimpan',
                                            array(
                                                '%siswa%' => $entity->getSiswa()->getNamaLengkap()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl(
                                            $this->ambilRuteAsal() == 'pendaftar' ? 'ortuwali-pendaftar_show'
                                                    : 'ortuwali-siswa_show',
                                            array(
                                                'sid' => $sid, 'id' => $entity->getId()
                                            )));
        }

        return array(
                'entity' => $entity, 'form' => $form->createView(),
                'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid),
                'ruteasal' => $this->ambilRuteAsal(),
        );
    }

    /**
     * Displays a form to edit an existing OrangtuaWali entity.
     *
     * @Route("/pendaftar/{id}/edit", name="ortuwali-pendaftar_edit")
     * @Route("/siswa/{id}/edit", name="ortuwali-siswa_edit")
     * @Template()
     */
    public function editAction($sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity OrangtuaWali tak ditemukan.');
        }

        $editForm = $this->createForm(new OrangtuaWaliType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid),
                'ruteasal' => $this->ambilRuteAsal(),
        );
    }

    /**
     * Edits an existing OrangtuaWali entity.
     *
     * @Route("/pendaftar/{id}/update", name="ortuwali-pendaftar_update")
     * @Route("/siswa/{id}/update", name="ortuwali-siswa_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:OrangtuaWali:edit.html.twig")
     */
    public function updateAction(Request $request, $sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity OrangtuaWali tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new OrangtuaWaliType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.ortuwali.terbarui',
                                            array(
                                                '%siswa%' => $entity->getSiswa()->getNamaLengkap()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl(
                                            $this->ambilRuteAsal() == 'pendaftar' ? 'ortuwali-pendaftar_edit'
                                                    : 'ortuwali-siswa_edit',
                                            array(
                                                'sid' => $sid, 'id' => $id
                                            )));
        }

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid),
                'ruteasal' => $this->ambilRuteAsal(),
        );
    }

    /**
     * Deletes a OrangtuaWali entity.
     *
     * @Route("/pendaftar/{id}/delete", name="ortuwali-pendaftar_delete")
     * @Route("/siswa/{id}/delete", name="ortuwali-siswa_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $sid, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:OrangtuaWali')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity OrangtuaWali tak ditemukan.');
            }

            try {
                if ($entity->isAktif() === true) {
                    $message = $this->get('translator')->trans('exception.larangan.hapus.ortuwali');
                    throw new \Exception($message);
                }

                $em->remove($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.ortuwali.terhapus',
                                                array(
                                                    '%siswa%' => $entity->getSiswa()->getNamaLengkap()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this->get('session')->getFlashBag()
                    ->add('error',
                            $this->get('translator')
                                    ->trans('flash.ortuwali.gagal.dihapus',
                                            array(
                                                '%siswa%' => $entity->getSiswa()->getNamaLengkap()
                                            )));
        }

        return $this
                ->redirect(
                        $this
                                ->generateUrl(
                                        $this->ambilRuteAsal() == 'pendaftar' ? 'ortuwali-pendaftar'
                                                : 'ortuwali-siswa',
                                        array(
                                            'sid' => $sid,
                                        )));
    }

    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        if ($this->ambilRuteAsal() == 'pendaftar') {
            $menu['headings.academic']['links.registration']->setCurrent(true);
        } else {
            $menu['headings.academic']['links.data.student']->setCurrent(true);
        }
    }

    private function isRegisteredToSchool() {
        $user = $this->getUser();
        $sekolah = $user->getSekolah();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            return $sekolah;
        } else if ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.useadmin'));
        } else {
            throw new AccessDeniedException($this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
