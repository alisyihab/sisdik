<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * KehadiranSiswa
 *
 * @ORM\Table(name="kehadiran_siswa", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="siswa_UNIQUE1", columns={"siswa_id", "tanggal"})
 * })
 * @ORM\Entity
 */
class KehadiranSiswa
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="status_kehadiran", type="string", length=100, nullable=false)
     */
    private $statusKehadiran;

    /**
     * @var boolean
     *
     * @ORM\Column(name="permulaan", type="boolean", nullable=false, options={"default"=1})
     */
    private $permulaan = true;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="tanggal", type="date", nullable=true)
     */
    private $tanggal;

    /**
     * @var string
     *
     * @ORM\Column(name="jam", type="string", length=10, nullable=true)
     */
    private $jam;

    /**
     * @var integer
     *
     * @ORM\Column(name="sms_dlr", type="smallint", nullable=true)
     */
    private $smsDlr;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sms_dlrtime", type="datetime", nullable=true)
     */
    private $smsDlrtime;

    /**
     * @var boolean
     *
     * @ORM\Column(name="sms_terproses", type="boolean", nullable=false, options={"default"=0})
     */
    private $smsTerproses = false;

    /**
     * @var string
     *
     * @ORM\Column(name="keterangan_status", type="string", length=45, nullable=true)
     */
    private $keteranganStatus;

    /**
     * @var \Sekolah
     *
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sekolah_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $sekolah;

    /**
     * @var \TahunAkademik
     *
     * @ORM\ManyToOne(targetEntity="TahunAkademik")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tahun_akademik_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $tahunAkademik;

    /**
     * @var \Kelas
     *
     * @ORM\ManyToOne(targetEntity="Kelas")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="kelas_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $kelas;

    /**
     * @var \Siswa
     *
     * @ORM\ManyToOne(targetEntity="Siswa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="siswa_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $siswa;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set statusKehadiran
     *
     * @param string $statusKehadiran
     * @return KehadiranSiswa
     */
    public function setStatusKehadiran($statusKehadiran) {
        $this->statusKehadiran = $statusKehadiran;

        return $this;
    }

    /**
     * Get statusKehadiran
     *
     * @return string
     */
    public function getStatusKehadiran() {
        return $this->statusKehadiran;
    }

    /**
     * Set permulaan
     *
     * @param boolean $permulaan
     * @return KehadiranSiswa
     */
    public function setPermulaan($permulaan) {
        $this->permulaan = $permulaan;

        return $this;
    }

    /**
     * Is permulaan
     *
     * @return boolean
     */
    public function isPermulaan() {
        return $this->permulaan;
    }

    /**
     * Set tanggal
     *
     * @param \DateTime $tanggal
     * @return KehadiranSiswa
     */
    public function setTanggal($tanggal) {
        $this->tanggal = $tanggal;

        return $this;
    }

    /**
     * Get tanggal
     *
     * @return \DateTime
     */
    public function getTanggal() {
        return $this->tanggal;
    }

    /**
     * Set jam
     *
     * @param string $jam
     * @return KehadiranSiswa
     */
    public function setJam($jam) {
        $this->jam = $jam;

        return $this;
    }

    /**
     * Get jam
     *
     * @return string
     */
    public function getJam() {
        return $this->jam;
    }

    /**
     * Set smsDlr
     *
     * @param integer $smsDlr
     * @return KehadiranSiswa
     */
    public function setSmsDlr($smsDlr) {
        $this->smsDlr = $smsDlr;

        return $this;
    }

    /**
     * Get smsDlr
     *
     * @return integer
     */
    public function getSmsDlr() {
        return $this->smsDlr;
    }

    /**
     * Set smsDlrtime
     *
     * @param \DateTime $smsDlrtime
     * @return KehadiranSiswa
     */
    public function setSmsDlrtime($smsDlrtime) {
        $this->smsDlrtime = $smsDlrtime;

        return $this;
    }

    /**
     * Get smsDlrtime
     *
     * @return \DateTime
     */
    public function getSmsDlrtime() {
        return $this->smsDlrtime;
    }

    /**
     * Set smsTerproses
     *
     * @param boolean $smsTerproses
     * @return KehadiranSiswa
     */
    public function setSmsTerproses($smsTerproses) {
        $this->smsTerproses = $smsTerproses;

        return $this;
    }

    /**
     * Get smsTerproses
     *
     * @return boolean
     */
    public function isSmsTerproses() {
        return $this->smsTerproses;
    }

    /**
     * Set keteranganStatus
     *
     * @param string $keteranganStatus
     * @return KehadiranSiswa
     */
    public function setKeteranganStatus($keteranganStatus) {
        $this->keteranganStatus = $keteranganStatus;

        return $this;
    }

    /**
     * Get keteranganStatus
     *
     * @return string
     */
    public function getKeteranganStatus() {
        return $this->keteranganStatus;
    }

    /**
     * Set sekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @return KehadiranSiswa
     */
    public function setSekolah(\Fast\SisdikBundle\Entity\Sekolah $sekolah = null) {
        $this->sekolah = $sekolah;

        return $this;
    }

    /**
     * Get sekolah
     *
     * @return \Fast\SisdikBundle\Entity\Sekolah
     */
    public function getSekolah() {
        return $this->sekolah;
    }

    /**
     * Set tahunAkademik
     *
     * @param \Fast\SisdikBundle\Entity\TahunAkademik $tahunAkademik
     * @return KehadiranSiswa
     */
    public function setTahunAkademik(\Fast\SisdikBundle\Entity\TahunAkademik $tahunAkademik = null) {
        $this->tahunAkademik = $tahunAkademik;

        return $this;
    }

    /**
     * Get tahunAkademik
     *
     * @return \Fast\SisdikBundle\Entity\TahunAkademik
     */
    public function getTahunAkademik() {
        return $this->tahunAkademik;
    }

    /**
     * Set kelas
     *
     * @param \Fast\SisdikBundle\Entity\Kelas $kelas
     * @return KehadiranSiswa
     */
    public function setKelas(\Fast\SisdikBundle\Entity\Kelas $kelas = null) {
        $this->kelas = $kelas;

        return $this;
    }

    /**
     * Get kelas
     *
     * @return \Fast\SisdikBundle\Entity\Kelas
     */
    public function getKelas() {
        return $this->kelas;
    }

    /**
     * Set siswa
     *
     * @param \Fast\SisdikBundle\Entity\Siswa $siswa
     * @return KehadiranSiswa
     */
    public function setSiswa(\Fast\SisdikBundle\Entity\Siswa $siswa = null) {
        $this->siswa = $siswa;

        return $this;
    }

    /**
     * Get siswa
     *
     * @return \Fast\SisdikBundle\Entity\Siswa
     */
    public function getSiswa() {
        return $this->siswa;
    }
}
