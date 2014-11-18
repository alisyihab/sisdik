<?php
namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\TahunAkademik;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\KehadiranSiswa;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\SiswaKelas;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Filesystem\Filesystem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/laporan-kehadiran-siswa")
 * @PreAuthorize("hasRole('ROLE_GURU_PIKET') or hasRole('ROLE_GURU')")
 */
class KehadiranSiswaLaporanController extends Controller
{
    const DOCUMENTS_BASEDIR = "/documents/base/";
    const BASEFILE = "base.ods";
    const OUTPUTFILE = "laporan-kehadiran-siswa.";
    const DOCUMENTS_OUTPUTDIR = "uploads/sekolah/laporan-kehadiran/";

    /**
     * @Route("/", name="laporan-kehadiran-siswa")
     * @Method("GET")
     * @Template("LanggasSisdikBundle:KehadiranSiswa:laporan.html.twig")
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_kehadiransiswa_carilaporan');

        $hariIni = new \DateTime();
        $searchform->get('hinggaTanggal')->setData($hariIni);

        $tahunAkademik = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
            ->findOneBy([
                'aktif' => true,
                'sekolah' => $sekolah,
            ])
        ;

        if (!(is_object($tahunAkademik) && $tahunAkademik instanceof TahunAkademik)) {
            throw $this->createNotFoundException($this->get('translator')->trans('flash.tahun.akademik.tidak.ada.yang.aktif'));
        }

        return [
            'searchform' => $searchform->createView(),
            'tahunAkademik' => $tahunAkademik,
        ];
    }

    /**
     * @Route("/lihat", name="laporan-kehadiran-siswa_lihat")
     * @Method("GET")
     * @Template("LanggasSisdikBundle:KehadiranSiswa:laporan.html.twig")
     */
    public function lihatAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $tahunAkademik = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
            ->findOneBy([
                'aktif' => true,
                'sekolah' => $sekolah->getId(),
            ])
        ;

        if (!(is_object($tahunAkademik) && $tahunAkademik instanceof TahunAkademik)) {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.tahun.akademik.tidak.ada.yang.aktif'))
            ;

            return $this->redirect($this->generateUrl('laporan-kehadiran-siswa'));
        }

        $siswaKelas = $em->createQueryBuilder()
            ->select('siswaKelas, siswa, orangtuaWali')
            ->from('LanggasSisdikBundle:SiswaKelas', 'siswaKelas')
            ->leftJoin('siswaKelas.tahunAkademik', 'tahunAkademik')
            ->leftJoin('siswaKelas.siswa', 'siswa')
            ->leftJoin('siswa.orangtuaWali', 'orangtuaWali')
            ->where('tahunAkademik.sekolah = :sekolah')
            ->andWhere('siswaKelas.tahunAkademik = :tahunAkademik')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('tahunAkademik', $tahunAkademik)
            ->orderBy('siswa.nomorInduk', 'ASC')
            ->addOrderBy('siswa.namaLengkap', 'ASC')
        ;

        $kehadiranSiswa = $em->createQueryBuilder()
            ->select('kehadiranSiswa')
            ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiranSiswa')
            ->where('kehadiranSiswa.sekolah = :sekolah')
            ->andWhere('kehadiranSiswa.tahunAkademik = :tahunAkademik')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('tahunAkademik', $tahunAkademik)
        ;

        $searchform = $this->createForm('sisdik_kehadiransiswa_carilaporan');
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        if ($searchform->isValid()) {
            if ($searchdata['kelas'] instanceof Kelas) {
                $siswaKelas
                    ->andWhere('siswaKelas.kelas = :kelas')
                    ->setParameter('kelas', $searchdata['kelas'])
                ;

                $kehadiranSiswa
                    ->andWhere('kehadiranSiswa.kelas = :kelas')
                    ->setParameter('kelas', $searchdata['kelas'])
                ;
            }

            $dariTanggal = $searchdata['dariTanggal'];
            $hinggaTanggal = $searchdata['hinggaTanggal'];

            if ($dariTanggal instanceof \DateTime && $hinggaTanggal instanceof \DateTime) {
                $kehadiranSiswa
                    ->andWhere('kehadiranSiswa.tanggal >= :dariTanggal AND kehadiranSiswa.tanggal <= :hinggaTanggal')
                    ->setParameter('dariTanggal', $dariTanggal->format("Y-m-d 00:00:00"))
                    ->setParameter('hinggaTanggal', $hinggaTanggal->format("Y-m-d 24:00:00"))
                ;
            } elseif (!($dariTanggal instanceof \DateTime) && $hinggaTanggal instanceof \DateTime) {
                $kehadiranSiswa
                    ->andWhere('kehadiranSiswa.tanggal = :hinggaTanggal')
                    ->setParameter('hinggaTanggal', $hinggaTanggal->format("Y-m-d"))
                ;
            } else {
                $searchdata['hinggaTanggal'] = $hariIni = new \DateTime();
                $kehadiranSiswa
                    ->andWhere('kehadiranSiswa.tanggal = :hinggaTanggal')
                    ->setParameter('hinggaTanggal', $hariIni->format("Y-m-d"))
                ;
            }

            if ($searchdata['searchkey'] != '') {
                $siswaKelas
                    ->andWhere("siswa.namaLengkap LIKE :searchkey OR siswa.nomorInduk LIKE :searchkey OR siswa.nomorIndukSistem = :searchkey2")
                    ->setParameter('searchkey', "%{$searchdata['searchkey']}%")
                    ->setParameter('searchkey2', $searchdata['searchkey'])
                ;
            }

            if ($searchdata['statusKehadiran'] != '') {
                $kehadiranSiswa
                    ->andWhere("kehadiranSiswa.statusKehadiran = :statusKehadiran")
                    ->setParameter('statusKehadiran', $searchdata['statusKehadiran'])
                ;
            }
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('parameter.pencarian.laporan.tidak.boleh.kosong'))
            ;

            return $this->redirect($this->generateUrl('laporan-kehadiran-siswa'));
        }

        $daftarStatusKehadiran = JadwalKehadiran::getDaftarStatusKehadiran();
        $daftarKehadiran = [];
        $daftarSiswaDiKelas = $siswaKelas->getQuery()->getResult();
        $kehadiranSiswaTotal = [];

        foreach ($daftarSiswaDiKelas as $siswaDiKelas) {
            $tmpKehadiran = [];
            if ($siswaDiKelas instanceof SiswaKelas) {
                $tmpKehadiran['siswa'] = $siswaDiKelas->getSiswa();
                $kehadiranSiswa
                    ->andWhere('kehadiranSiswa.siswa = :siswa')
                    ->setParameter('siswa', $siswaDiKelas->getSiswa())
                ;
                $kehadiranPerSiswa = $kehadiranSiswa->getQuery()->getResult();

                foreach ($daftarStatusKehadiran as $key => $value) {
                    $tmpJumlahStatus = 0;
                    foreach ($kehadiranPerSiswa as $kehadiran) {
                        if ($kehadiran instanceof KehadiranSiswa) {
                            if ($key == $kehadiran->getStatusKehadiran()) {
                                $tmpJumlahStatus++;
                            }
                        }
                    }
                    $tmpKehadiran[$key] = $tmpJumlahStatus;
                    $kehadiranSiswaTotal[$key] = isset($kehadiranSiswaTotal[$key]) ? $kehadiranSiswaTotal[$key] + $tmpJumlahStatus : $tmpJumlahStatus;
                }

                $tmpKehadiran['jumlahHadir'] = $tmpKehadiran['a-hadir-tepat'] + $tmpKehadiran['b-hadir-telat'];
                $tmpKehadiran['jumlahTidakHadir'] = $tmpKehadiran['c-alpa'] + $tmpKehadiran['d-izin'] + $tmpKehadiran['e-sakit'];
                $daftarKehadiran[] = $tmpKehadiran;
            }
        }

        return [
            'searchkey' => $searchdata['searchkey'],
            'searchform' => $searchform->createView(),
            'kelas' => $searchdata['kelas'],
            'tahunAkademik' => $tahunAkademik,
            'dariTanggal' => $searchdata['dariTanggal'],
            'hinggaTanggal' => $searchdata['hinggaTanggal'],
            'daftarStatusKehadiran' => $daftarStatusKehadiran,
            'kehadiranSiswa' => $daftarKehadiran,
            'kehadiranSiswaTotal' => $kehadiranSiswaTotal,
        ];
    }

    /**
     * @Route("/ekspor", name="laporan-kehadiran-siswa_ekspor")
     * @Method("POST")
     */
    public function eksporAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $tahunAkademik = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
            ->findOneBy([
                'aktif' => true,
                'sekolah' => $sekolah->getId(),
            ])
        ;

        if (!(is_object($tahunAkademik) && $tahunAkademik instanceof TahunAkademik)) {
            $return = [
                "error" => $this->get('translator')->trans('flash.tahun.akademik.tidak.ada.yang.aktif'),
            ];

            $return = json_encode($return);

            return new Response($return, 200, [
                'Content-Type' => 'application/json',
            ]);
        }

        $siswaKelas = $em->createQueryBuilder()
            ->select('siswaKelas, siswa')
            ->from('LanggasSisdikBundle:SiswaKelas', 'siswaKelas')
            ->leftJoin('siswaKelas.tahunAkademik', 'tahunAkademik')
            ->leftJoin('siswaKelas.siswa', 'siswa')
            ->where('tahunAkademik.sekolah = :sekolah')
            ->andWhere('siswaKelas.tahunAkademik = :tahunAkademik')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('tahunAkademik', $tahunAkademik)
            ->orderBy('siswa.nomorInduk', 'ASC')
            ->addOrderBy('siswa.namaLengkap', 'ASC')
        ;

        $kehadiranSiswa = $em->createQueryBuilder()
            ->select('kehadiranSiswa')
            ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiranSiswa')
            ->where('kehadiranSiswa.sekolah = :sekolah')
            ->andWhere('kehadiranSiswa.tahunAkademik = :tahunAkademik')
            ->andWhere('kehadiranSiswa.siswa = :siswa')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('tahunAkademik', $tahunAkademik)
        ;

        $searchform = $this->createForm('sisdik_kehadiransiswa_carilaporan');
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        if ($searchform->isValid()) {
            if ($searchdata['kelas'] instanceof Kelas) {
                $siswaKelas
                    ->andWhere('siswaKelas.kelas = :kelas')
                    ->setParameter('kelas', $searchdata['kelas'])
                ;
            }

            $dariTanggal = $searchdata['dariTanggal'];
            $hinggaTanggal = $searchdata['hinggaTanggal'];

            if ($dariTanggal instanceof \DateTime && $hinggaTanggal instanceof \DateTime) {
                $kehadiranSiswa
                    ->andWhere('kehadiranSiswa.tanggal >= :dariTanggal AND kehadiranSiswa.tanggal <= :hinggaTanggal')
                    ->setParameter('dariTanggal', $dariTanggal->format("Y-m-d 00:00:00"))
                    ->setParameter('hinggaTanggal', $hinggaTanggal->format("Y-m-d 24:00:00"))
                ;
            } elseif (!($dariTanggal instanceof \DateTime) && $hinggaTanggal instanceof \DateTime) {
                $kehadiranSiswa
                    ->andWhere('kehadiranSiswa.tanggal = :hinggaTanggal')
                    ->setParameter('hinggaTanggal', $hinggaTanggal->format("Y-m-d"))
                ;
            } else {
                $searchdata['hinggaTanggal'] = $hariIni = new \DateTime();
                $kehadiranSiswa
                    ->andWhere('kehadiranSiswa.tanggal = :hinggaTanggal')
                    ->setParameter('hinggaTanggal', $hariIni->format("Y-m-d"))
                ;
            }
        } else {
            $return = [
                "error" => $this->get('translator')->trans('parameter.pencarian.laporan.tidak.boleh.kosong'),
            ];

            $return = json_encode($return);

            return new Response($return, 200, [
                'Content-Type' => 'application/json',
            ]);
        }

        $daftarStatusKehadiran = JadwalKehadiran::getDaftarStatusKehadiran();
        $daftarKehadiran = [];
        $daftarSiswaDiKelas = $siswaKelas->getQuery()->getResult();

        foreach ($daftarSiswaDiKelas as $siswaDiKelas) {
            $tmpKehadiran = [];
            if ($siswaDiKelas instanceof SiswaKelas) {
                $tmpKehadiran['siswa'] = $siswaDiKelas->getSiswa();
                $kehadiranSiswa->setParameter('siswa', $siswaDiKelas->getSiswa());
                $kehadiranPerSiswa = $kehadiranSiswa->getQuery()->getResult();

                foreach ($daftarStatusKehadiran as $key => $value) {
                    $tmpJumlahStatus = 0;
                    foreach ($kehadiranPerSiswa as $kehadiran) {
                        if ($kehadiran instanceof KehadiranSiswa) {
                            if ($key == $kehadiran->getStatusKehadiran()) {
                                $tmpJumlahStatus++;
                            }
                        }
                    }
                    $tmpKehadiran[$key] = $tmpJumlahStatus;
                }

                $tmpKehadiran['jumlahHadir'] = $tmpKehadiran['a-hadir-tepat'] + $tmpKehadiran['b-hadir-telat'];
                $tmpKehadiran['jumlahTidakHadir'] = $tmpKehadiran['c-alpa'] + $tmpKehadiran['d-izin'] + $tmpKehadiran['e-sakit'];
                $daftarKehadiran[] = $tmpKehadiran;
            }
        }

        return [
            'searchform' => $searchform->createView(),
        ];

        $documentbase = $this->get('kernel')->getRootDir() . self::DOCUMENTS_BASEDIR . self::BASEFILE;
        $outputdir = self::DOCUMENTS_OUTPUTDIR;

        $filenameoutput = self::OUTPUTFILE . date("Y-m-d h:i") . ".sisdik";

        $outputfiletype = "ods";
        $extensiontarget = $extensionsource = ".$outputfiletype";
        $filesource = $filenameoutput . $extensionsource;
        $filetarget = $filenameoutput . $extensiontarget;

        $fs = new Filesystem();
        if (!$fs->exists($outputdir . $sekolah->getId() . '/')) {
            $fs->mkdir($outputdir . $sekolah->getId() . '/');
        }

        $documentsource = $outputdir . $sekolah->getId() . '/' . $filesource;
        $documenttarget = $outputdir . $sekolah->getId() . '/' . $filetarget;

        if ($outputfiletype == 'ods') {
            if (copy($documentbase, $documenttarget) === TRUE) {
                $ziparchive = new \ZipArchive();
                $ziparchive->open($documenttarget);
                $ziparchive->addFromString('content.xml', $this->renderView("LanggasSisdikBundle:KehadiranSiswa:laporan.xml.twig", [
                        'kelas' => $searchdata['kelas'],
                        'tahunAkademik' => $tahunAkademik,
                        'dariTanggal' => $searchdata['dariTanggal'],
                        'hinggaTanggal' => $searchdata['hinggaTanggal'],
                        'daftarStatusKehadiran' => $daftarStatusKehadiran,
                        'kehadiranSiswa' => $daftarKehadiran,
                    ])
                );

                if ($ziparchive->close() === TRUE) {
                    $return = [
                        "redirectUrl" => $this->generateUrl("laporan-kehadiran-siswa_unduh", [
                            'filename' => $filetarget,
                        ]),
                        "filename" => $filetarget,
                    ];

                    $return = json_encode($return);

                    return new Response($return, 200, [
                        'Content-Type' => 'application/json',
                    ]);
                }
            }
        }

        $return = [
            "error" => $this->get('translator')->trans('errorinfo.tak.ada.kehadiran.siswa'),
        ];

        $return = json_encode($return);

        return new Response($return, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * @Route("/unduh/{filename}/{type}", name="laporan-kehadiran-siswa_unduh")
     * @Method("GET")
     */
    public function unduhAction($filename, $type = 'ods')
    {
        $sekolah = $this->getSekolah();

        $filetarget = $filename;
        $documenttarget = self::DOCUMENTS_OUTPUTDIR . $sekolah->getId() . '/' . $filetarget;

        $response = new Response(file_get_contents($documenttarget), 200);
        $doc = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filetarget);
        $response->headers->set('Content-Disposition', $doc);
        $response->headers->set('Content-Description', 'Laporan Pendaftaran');

        if ($type == 'ods') {
            $response->headers->set('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        } elseif ($type == 'pdf') {
            $response->headers->set('Content-Type', 'application/pdf');
        }

        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Expires', '0');
        $response->headers->set('Cache-Control', 'must-revalidate');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Content-Length', filesize($documenttarget));

        return $response;
    }

    private function setCurrentMenu()
    {
        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$this->get('translator')->trans('headings.presence', array(), 'navigations')][$this->get('translator')->trans('links.laporan.kehadiran.siswa', array(), 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
