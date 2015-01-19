<?php 
// src/Foment/GestioBundle/Entity/AuxBanc.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(name="auxbancs")
 */
class AuxBanc
{
	/**
     * @ORM\Column(type="smallint")
     * @ORM\Id
     */
    protected $codi;
    
    /**
     * @ORM\Column(type="string", length=75, nullable=false)
     * 
     */
    protected $nom;
    
    /*
        INSERT INTO auxbancs (codi, nom) VALUES (0011,'Allfunds Bank');
		INSERT INTO auxbancs (codi, nom) VALUES (0136,'Aresbank');
		INSERT INTO auxbancs (codi, nom) VALUES (0061,'Banca March');
		INSERT INTO auxbancs (codi, nom) VALUES (0078,'Banca Pueyo');
		INSERT INTO auxbancs (codi, nom) VALUES (0188,'Banco Alcala');
		INSERT INTO auxbancs (codi, nom) VALUES (0086,'Banco Banif');
		INSERT INTO auxbancs (codi, nom) VALUES (0182,'Banco Bilbao Vizcaya Argentaria');
		INSERT INTO auxbancs (codi, nom) VALUES (0130,'Banco Caixa Geral');
		INSERT INTO auxbancs (codi, nom) VALUES (0234,'Banco Caminos');
		INSERT INTO auxbancs (codi, nom) VALUES (0225,'Banco Cetelem');
		INSERT INTO auxbancs (codi, nom) VALUES (0198,'Banco Cooperativo Español');
		INSERT INTO auxbancs (codi, nom) VALUES (0091,'Banco de Albacete');
		INSERT INTO auxbancs (codi, nom) VALUES (2108,'Banco de Caja España Inver. Salamanca y Soria');
		INSERT INTO auxbancs (codi, nom) VALUES (0115,'Banco de Castilla-La Mancha');
		INSERT INTO auxbancs (codi, nom) VALUES (0003,'Banco de Depósitos');
		INSERT INTO auxbancs (codi, nom) VALUES (0059,'Banco de Madrid');
		INSERT INTO auxbancs (codi, nom) VALUES (0132,'Banco de Promoción de Negocios');
		INSERT INTO auxbancs (codi, nom) VALUES (0081,'Banco de Sabadell');
		INSERT INTO auxbancs (codi, nom) VALUES (0093,'Banco de Valencia');
		INSERT INTO auxbancs (codi, nom) VALUES (0057,'Banco Depositario BBVA');
		INSERT INTO auxbancs (codi, nom) VALUES (0030,'Banco Español de Crédito');
		INSERT INTO auxbancs (codi, nom) VALUES (0031,'Banco Etcheverria');
		INSERT INTO auxbancs (codi, nom) VALUES (0184,'Banco Europeo de Finanzas');
		INSERT INTO auxbancs (codi, nom) VALUES (0488,'Banco Financiero y de Ahorros');
		INSERT INTO auxbancs (codi, nom) VALUES (0220,'Banco Finantia Sofinloc');
		INSERT INTO auxbancs (codi, nom) VALUES (0046,'Banco Gallego');
		INSERT INTO auxbancs (codi, nom) VALUES (2086,'Banco Grupo Cajatres');
		INSERT INTO auxbancs (codi, nom) VALUES (0113,'Banco Industrial de Bilbao');
		INSERT INTO auxbancs (codi, nom) VALUES (0232,'Banco Inversis');
		INSERT INTO auxbancs (codi, nom) VALUES (0487,'Banco Mare Nostrum');
		INSERT INTO auxbancs (codi, nom) VALUES (0186,'Banco Mediolanum');
		INSERT INTO auxbancs (codi, nom) VALUES (0121,'Banco Occidental');
		INSERT INTO auxbancs (codi, nom) VALUES (0235,'Banco Pichincha España');
		INSERT INTO auxbancs (codi, nom) VALUES (0075,'Banco Popular Español');
		INSERT INTO auxbancs (codi, nom) VALUES (0238,'Banco Popular Pastor');
		INSERT INTO auxbancs (codi, nom) VALUES (0049,'Banco Santander');
		INSERT INTO auxbancs (codi, nom) VALUES (0125,'Bancofar');
		INSERT INTO auxbancs (codi, nom) VALUES (0229,'Bancopopular-E');
		INSERT INTO auxbancs (codi, nom) VALUES (0038,'Banesto Banco de Emisiones');
		INSERT INTO auxbancs (codi, nom) VALUES (0099,'Bankia Banca Privada');
		INSERT INTO auxbancs (codi, nom) VALUES (2038,'Bankia');
		INSERT INTO auxbancs (codi, nom) VALUES (0128,'Bankinter');
		INSERT INTO auxbancs (codi, nom) VALUES (0138,'Bankoa');
		INSERT INTO auxbancs (codi, nom) VALUES (0219,'Banque Marocaine Commerce Exterieur Internat');
		INSERT INTO auxbancs (codi, nom) VALUES (0065,'Barclays Bank');
		INSERT INTO auxbancs (codi, nom) VALUES (0237,'BBK Banl Cajasur');
		INSERT INTO auxbancs (codi, nom) VALUES (0129,'BBVA Banco de Financiación');
		INSERT INTO auxbancs (codi, nom) VALUES (0058,'BNP Paribas España');
		INSERT INTO auxbancs (codi, nom) VALUES (2100,'Caixabank');
		INSERT INTO auxbancs (codi, nom) VALUES (2013,'Catalunya Banc');
		INSERT INTO auxbancs (codi, nom) VALUES (2000,'Cecabank');
		INSERT INTO auxbancs (codi, nom) VALUES (0122,'Citibank España');
		INSERT INTO auxbancs (codi, nom) VALUES (0019,'Deutsche Bank');
		INSERT INTO auxbancs (codi, nom) VALUES (0231,'Dexia Sabadell');
		INSERT INTO auxbancs (codi, nom) VALUES (0211,'EBN Banco de Negocios');
		INSERT INTO auxbancs (codi, nom) VALUES (0223,'General Electric Capital Bank');
		INSERT INTO auxbancs (codi, nom) VALUES (2085,'Iibercaja Banco');
		INSERT INTO auxbancs (codi, nom) VALUES (2095,'Kutxabank');
		INSERT INTO auxbancs (codi, nom) VALUES (2048,'Liberbank');
		INSERT INTO auxbancs (codi, nom) VALUES (0236,'Lloyds Bank International');
		INSERT INTO auxbancs (codi, nom) VALUES (2080,'NCG Banco');
		INSERT INTO auxbancs (codi, nom) VALUES (0133,'Nuevo Micro Bank');
		INSERT INTO auxbancs (codi, nom) VALUES (0073,'Open Bank');
		INSERT INTO auxbancs (codi, nom) VALUES (0233,'Popular Banca Privada');
		INSERT INTO auxbancs (codi, nom) VALUES (0200,'Privat Bank Degroof');
		INSERT INTO auxbancs (codi, nom) VALUES (0094,'RBC Investor Services España');
		INSERT INTO auxbancs (codi, nom) VALUES (0083,'Renta 4 Banco');
		INSERT INTO auxbancs (codi, nom) VALUES (0224,'Santander Consumer Finance');
		INSERT INTO auxbancs (codi, nom) VALUES (0036,'Santander Investment');
		INSERT INTO auxbancs (codi, nom) VALUES (1490,'Self Trade Bank');
		INSERT INTO auxbancs (codi, nom) VALUES (0216,'Targobank');
		INSERT INTO auxbancs (codi, nom) VALUES (0226,'UBS BANK');
		INSERT INTO auxbancs (codi, nom) VALUES (2103,'Unicaja Banco');
		INSERT INTO auxbancs (codi, nom) VALUES (2107,'Unnim Banc');
		INSERT INTO auxbancs (codi, nom) VALUES (0227,'Unoe Bank');

     * 
     */


    /**
     * Set codi
     *
     * @param integer $codi
     * @return AuxBanc
     */
    public function setCodi($codi)
    {
        $this->codi = $codi;

        return $this;
    }

    /**
     * Get codi
     *
     * @return integer 
     */
    public function getCodi()
    {
        return $this->codi;
    }

    /**
     * Set nom
     *
     * @param string $nom
     * @return AuxBanc
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string 
     */
    public function getNom()
    {
        return $this->nom;
    }
}
