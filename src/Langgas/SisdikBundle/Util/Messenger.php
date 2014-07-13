<?php
namespace Langgas\SisdikBundle\Util;

use Doctrine\Common\Persistence\ObjectManager;
use Langgas\SisdikBundle\Entity\LogsmsKeluar;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * Pengirim pesan -- the Messenger
 */
class Messenger
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var string
     */
    private $provider;

    /**
     * @var string
     */
    private $scheme;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $port;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $resource;

    /**
     * @var string
     */
    private $apikey;

    /**
     * @var string
     */
    private $report;

    /**
     * @var string
     */
    private $phonenumber;

    /**
     * @var string
     */
    private $message;

    /**
     * @var integer
     */
    private $logid = 0;

    /**
     * @var string
     */
    private $deliveryReportURL;

    /**
     * @var string
     */
    private $apiResult;

    /**
     * @param ObjectManager $objectManager
     * @param Router $router
     * @param string $provider
     * @param string $scheme
     * @param string $host
     * @param string $port
     * @param string $user
     * @param string $password
     * @param string $resource
     * @param string $apikey
     * @param string $report
     */
    public function __construct(
        ObjectManager $objectManager,
        Router $router,
        $provider,
        $scheme,
        $host,
        $port,
        $user,
        $password,
        $resource,
        $apikey,
        $report)
    {
        $this->objectManager = $objectManager;
        $this->router = $router;
        $this->provider = $provider;
        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->resource = $resource;
        $this->apikey = $apikey;
        $this->report = $report;
    }

    /**
     * @param string $phonenumber
     */
    public function setPhonenumber($phonenumber)
    {
        $this->phonenumber = $phonenumber;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @param  Sekolah $sekolah
     * @return integer
     */
    public function setLogEntry(Sekolah $sekolah = null)
    {
        $em = $this->objectManager;

        $log = new LogsmsKeluar();
        $log->setPenyediaApi($this->provider);
        $log->setKe($this->phonenumber);
        $log->setTeks($this->message);
        $log->setSekolah($sekolah);
        $log->setWaktuPanggilApi(new \DateTime());

        $em->persist($log);
        $em->flush();

        $this->logid = $log->getId();

        return $this->logid;
    }

    /**
     * @param string $url
     * @param string $hasil
     */
    private function updateLogHasilAPI($url, $hasil)
    {
        $em = $this->objectManager;

        /* @var $entity LogsmsKeluar */
        $entity = $em->getRepository('LanggasSisdikBundle:LogsmsKeluar')->find($this->logid);
        $entity->setApiTerpanggil($url);
        $entity->setHasilAPI($hasil);

        $em->persist($entity);
        $em->flush();
    }

    /**
     * Set delivery report controller
     *
     * @param string $url
     */
    public function setDeliveryReportURL($url)
    {
        $this->deliveryReportURL = $url;
    }

    /**
     * @param  Sekolah $sekolah
     * @return integer
     */
    public function sendMessage(Sekolah $sekolah = null)
    {
        if ($this->logid == 0) {
            $this->setLogEntry($sekolah);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 13);

        if ($this->provider == "local") {

            $param = "?username="
                . $this->user
                . "&password="
                . $this->password
                . "&to="
                . $this->phonenumber
                . "&text="
                . urlencode($this->message)
            ;

            if ($this->report == "1") {
                $this
                    ->setDeliveryReportURL(
                        $this
                            ->router
                            ->generate(
                                "localapi_logsmskeluar_dlr_update",
                                [
                                    'logid' => $this->logid,
                                    'status' => "%d",
                                    'time' => "%T"
                                ],
                                UrlGeneratorInterface::ABSOLUTE_URL
                            )
                    )
                ;

                $param .= "&dlr-mask=7&dlr-url=" . urlencode($this->deliveryReportURL);
            }

            $url = $this->scheme . "://" . $this->host . ":" . $this->port . $this->resource . $param;

            curl_setopt($ch, CURLOPT_URL, $url);
            $hasil = curl_exec($ch);

            $this->updateLogHasilAPI($url, $hasil);

        } elseif ($this->provider == "rajasms") {

            $param = "?nohp="
                . $this->phonenumber
                . "&pesan="
                . urlencode($this->message)
                . "&key="
                . $this->apikey
            ;

            $url = $this->scheme . "://" . $this->host . $this->resource . $param;

            curl_setopt($ch, CURLOPT_URL, $url);
            $hasil = curl_exec($ch);

            $this->updateLogHasilAPI($url, $hasil);

        } elseif ($this->provider == "zenziva") {

            $param = "?userkey="
                . $this->user
                . "&passkey="
                . $this->password
                . "&nohp="
                . $this->phonenumber
                . "&pesan="
                . urlencode($this->message)
            ;

            $url = $this->scheme . "://" . $this->host . $this->resource . $param;

            curl_setopt($ch, CURLOPT_URL, $url);
            $hasil = curl_exec($ch);

            $this->updateLogHasilAPI($url, $hasil);

        }

        curl_close($ch);
    }
}