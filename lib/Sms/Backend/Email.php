<?php
/**
 * Sms backend e-mail to sms
 *
 * @package Sms
 * @version $Id$
 */
require_once 'lib/Sms/Backend.php';
require_once 'lib/Sms/Message.php';
require_once 'Zend/Mail.php';
/**
 * Sms backend e-mail to sms
 *
 * @package Sms
 */
class Sms_Backend_Email implements Sms_Backend
{
    const TYPE = 'email';

    public static $smslist = array(
    'US'=>array(    /* United States */
        '3 River Wireless'=>'{n}@sms.3rivers.net',
        '7-11 Speakout'=>'{n}@cingularme.com',
        'Advantage Communications'=>'{n}@advantagepaging.com',
        'Airtouch Pagers'=>'{n}@airtouch.net',
        'Airtouch Pagers'=>'{n}@airtouch.net',
        'Airtouch Pagers'=>'{n}@airtouchpaging.com',
        'Airtouch Pagers'=>'{n}@alphapage.airtouch.com',
        'Airtouch Pagers'=>'{n}@myairmail.com',
        'AllTel'=>'{n}@message.alltel.com',
        'Alltel PCS'=>'{n}@message.alltel.com',
        'Alltel'=>'{n}@alltelmessage.com',
        'AirVoice'=>'{n}@mmode.com',
        'Ameritech'=>'{n}@pagin.acswireless.com',
        'Aql'=>'{n}@text.aql.com',
        'Arch Pagers (PageNet)'=>'{n}@archwireless.net',
        'Arch Pagers (PageNet)'=>'{n}@epage.arch.com',
        'AT&T Wireless'=>'{n}@txt.att.net',
        'Bell South'=>'{n}@blsdcs.net',
        'Bell South Blackberry'=>'{n}@bellsouthtips.com',
        'Bell South Mobility'=>'{n}@blsdcs.net',
        'Bell South SMS'=>'{n}@sms.bellsouth.com',
        'Bell South Wireless'=>'{n}@wireless.bellsouth.com',
        'Bluegrass Cellular'=>'{n}@sms.bluecell.com',
        'Boost Mobile'=>'{n}@myboostmobile.com',
        'Boost'=>'{n}@myboostmobile.com',
        'CallPlus'=>'{n}@mmode.com',
        'Carolina Mobile Communications'=>'{n}@cmcpaging.com',
        'Cellular One'=>'{n}@message.cellone-sf.com',
        'Cellular One East Coast'=>'{n}@phone.cellone.net',
        'Cellular One Mobile'=>'{n}@mobile.celloneusa.com',
        'Cellular One PCS'=>'{n}@paging.cellone-sf.com',
        'Cellular One SBC'=>'{n}@sbcemail.com',
        'Cellular One South West'=>'{n}@swmsg.com',
        'Cellular One West'=>'{n}@mycellone.com',
        'Cellular South'=>'{n}@csouth1.com',
        'Central Vermont Communications'=>'{n}@cvcpaging.com',
        'CenturyTel'=>'{n}@messaging.centurytel.net',
        'Cingular (GSM)'=>'{n}@cingularme.com',
        'Cingular (TDMA)'=>'{n}@mmode.com',
        'Cingular Wireless'=>'{n}@mobile.mycingular.net',
        'Cingular'=>'{n}@cingularme.com',
        'Communication Specialists'=>'{n}@pageme.comspeco.net',
        'Cook Paging'=>'{n}@cookmail.com',
        'Corr Wireless Communications'=>'{n}@corrwireless.net',
        'Cricket'=>'{n}@sms.mycricket.com',
        'Dobson Communications Corporation'=>'{n}@mobile.dobson.net',
        'Dobson-Alex Wireless / Dobson-Cellular One'=>'{n}@mobile.cellularone.com',
        'Edge Wireless'=>'{n}@sms.edgewireless.com',
        'Galaxy Corporation'=>'{n}@sendabeep.net',
        'GCS Paging'=>'{n}@webpager.us',
        'Globalstart'=>'{n}@msg.globalstarusa.com',
        'GTE'=>'{n}@gte.pagegate.net',
        'GTE'=>'{n}@messagealert.com',
        'GrayLink / Porta-Phone'=>'{n}@epage.porta-phone.com',
        'Houston Cellular'=>'{n}@text.houstoncellular.net',
        'Illinois Valley Cellular'=>'{n}@ivctext.com',
        'Inland Cellular Telephone'=>'{n}@inlandlink.com',
        'Iridium'=>'{n}@msg.iridium.com',
        'JSM Tele-Page'=>'{n}@jsmtel.com',
        'Lauttamus Communication'=>'{n}@e-page.net',
        'MCI Phone'=>'{n}@mci.com',
        'MCI'=>'{n}@pagemci.com',
        'Metro PCS'=>'{n}@mymetropcs.com',
        'Metrocall'=>'{n}@page.metrocall.com',
        'Metrocall 2-way'=>'{n}@my2way.com',
        'Midwest Wireless'=>'{n}@clearlydigital.com',
        'Mobilecom PA'=>'{n}@page.mobilcom.net',
        'Mobilfone'=>'{n}@page.mobilfone.com',
        'MobiPCS'=>'{n}@mobipcs.net',
        'Morris Wireless'=>'{n}@beepone.net',
        'Nextel'=>'{n}@messaging.nextel.com',
        'NPI Wireless'=>'{n}@npiwireless.com',
        'Ntelos'=>'{n}@pcs.ntelos.com',
        'O2'=>'{n}@mobile.celloneusa.com',
        'Omnipoint'=>'{n}@omnipoint.com',
        'Omnipoint'=>'{n}@omnipointpcs.com',
        'OnlineBeep'=>'{n}@onlinebeep.net',
        'Orange'=>'{n}@mobile.celloneusa.com',
        'PCS One'=>'{n}@pcsone.net',
        'Pacific Bell'=>'{n}@pacbellpcs.net',
        'PageMart'=>'{n}@pagemart.net',
        'PageOne NorthWest'=>'{n}@page1nw.com',
        'Pioneer / Enid Cellular'=>'{n}@msg.pioneerenidcellular.com',
        'Price Communications'=>'{n}@mobilecell1se.com',
        'ProPage'=>'{n}@page.propage.net',
        'Public Service Cellular'=>'{n}@sms.pscel.com',
        'Qualcomm'=>'name@pager.qualcomm.com',
        'Qwest'=>'{n}@qwestmp.com',
        'RAM Page'=>'{n}@ram-page.com',
        'Rogers Wireless'=>'{n}@pcs.rogers.com',
        'Safaricom'=>'{n}@safaricomsms.com',
        'Satelindo GSM'=>'{n}@satelindogsm.com',
        'Satellink'=>'{n}.pageme@satellink.net',
        'Simple Freedom'=>'{n}@text.simplefreedom.net',
        'Skytel Pagers'=>'{n}@email.skytel.com',
        'Skytel Pagers'=>'{n}@skytel.com',
        'Smart Telecom'=>'{n}@mysmart.mymobile.ph',
        'Southern LINC'=>'{n}@page.southernlinc.com',
        'Southwestern Bell'=>'{n}@email.swbw.com',
        'Sprint PCS'=>'{n}@messaging.sprintpcs.com',
        'ST Paging'=>'pin@page.stpaging.com',
        'SunCom'=>'{n}@tms.suncom.com',
        'Surewest Communications'=>'{n}@mobile.surewest.com',
        'T-Mobile'=>'{n}@tmomail.net',
        'Teleflip'=>'{n}@teleflip.com',
        'Teletouch'=>'{n}@pageme.teletouch.com',
        'Telus'=>'{n}@msg.telus.com',
        'The Indiana Paging Co'=>'{n}@pager.tdspager.com',
        'Tracfone'=>'{n}@mmst5.tracfone.com',
        'Triton'=>'{n}@tms.suncom.com',
        'TIM'=>'{n}@timnet.com',
        'TSR Wireless'=>'{n}@alphame.com',
        'TSR Wireless'=>'{n}@beep.com',
        'US Cellular'=>'{n}@email.uscc.net',
        'USA Mobility'=>'{n}@mobilecomm.net',
        'Unicel'=>'{n}@utext.com',
        'Verizon'=>'{n}@vtext.com',
        'Verizon PCS'=>'{n}@myvzw.com',
        'Verizon Pagers'=>'{n}@myairmail.com',
        'Virgin Mobile'=>'{n}@vmobl.com',
        'Virgin Mobile'=>'{n}@vxtras.com',
        'WebLink Wireless'=>'{n}@pagemart.net',
        'West Central Wireless'=>'{n}@sms.wcc.net',
        'Western Wireless'=>'{n}@cellularonewest.com',
        'Wyndtell'=>'{n}@wyndtell.com',
        ),
    'AR'=>array(    /* Argentina */
        'CTI'=>'{n}@sms.ctimovil.com.ar',
        'Movicom'=>'{n}@sms.movistar.net.ar',
        'Nextel'=>'TwoWay.11{n}@nextel.net.ar',
        'Personal'=>'{n}@alertas.personal.com.ar',
        ),
    'AW'=>array(    /* Aruba */
        'Setar Mobile'=>'297+{n}@mas.aw',
        ),
    'AU'=>array(    /* Australia */
        'Blue Sky Frog'=>'{n}@blueskyfrog.com',
        'Optus Mobile'=>'0{n}@optusmobile.com.au',
        'SL Interactive'=>'{n}@slinteractive.com.au',
        ),
    'AT'=>array(    /* Austria */
        'MaxMobil'=>'{n}x@max.mail.at',
        'One Connect'=>'{n}@onemail.at',
        'Provider'=>'E-mail to SMS address format',
        'T-Mobile'=>'43676{n}@sms.t-mobile.at',
        ),
    'BE'=>array(    /* Belgium */
        'Mobistar'=>'{n}@mobistar.be',
        ),
    'BM'=>array(    /* Bermuda */
        'Mobility'=>'{n}@ml.bm',
        ),
    'BR'=>array(    /* Brazil */
        'Claro'=>'{n}@clarotorpedo.com.br',
        'Nextel'=>'{n}@nextel.com.br',
        ),
    'BG'=>array(    /* Bulgaria */
        'Globul'=>'{n}@sms.globul.bg',
        'Mtel'=>'{n}@sms.mtel.net',
        ),
    'CA'=>array(    /* Canada */
        'Aliant'=>'{n}@wirefree.informe.ca',
        'Bell Mobility'=>'{n}@txt.bellmobility.ca',
        'Fido'=>'{n}@fido.ca',
        'Koodo Mobile'=>'{n}@msg.koodomobile.com',
        'Microcell'=>'{n}@fido.ca',
        'MTS Mobility'=>'{n}@text.mtsmobility.com',
        'NBTel'=>'{n}@wirefree.informe.ca',
        'PageMart'=>'{n}@pmcl.net',
        'PageNet'=>'{n}@pagegate.pagenet.ca',
        'Presidents Choice'=>'{n}@mobiletxt.ca',
        'Rogers Wireless'=>'{n}@pcs.rogers.com',
        'Sasktel Mobility'=>'{n}@pcs.sasktelmobility.com',
        'Telus'=>'{n}@msg.telus.com',
        'Virgin Mobile'=>'{n}@vmobile.ca',
        ),
    'CL'=>array(    /* Chile */
        'Bell South'=>'{n}@bellsouth.cl',
        ),
    'CO'=>array(    /* Columbia */
        'Comcel'=>'{n}@comcel.com.co',
        'Moviastar'=>'{n}@movistar.com.co',
        ),
    'CZ'=>array(    /* Czech Republic */
        'Eurotel'=>'+ccaa@sms.eurotel.cz',
        'Oskar'=>'{n}@mujoskar.cz',
        ),
    'DK'=>array(    /* Denmark */
        'Sonofon'=>'{n}@note.sonofon.dk',
        'Tele Danmark Mobil'=>'{n}@sms.tdk.dk',
        'Telia Denmark'=>'{n}@gsm1800.telia.dk',
        ),
    'EE'=>array(    /* Estonia */
        'EMT'=>'{n}@sms.emt.ee',
        ),
    'FR'=>array(    /* France */
        'SFR'=>'{n}@sfr.fr',
        ),
    'DE'=>array(    /* Germany */
        'E-Plus'=>'0{n}.sms@eplus.de',
        'Mannesmann Mobilefunk'=>'0{n}@d2-message.de',
        'O2'=>'0{n}@o2online.de',
        'T-Mobile'=>'{n}@t-mobile-sms.de',
        'Vodafone'=>'0{n}@vodafone-sms.de',
        ),
    'HU'=>array(    /* Hungary */
        'PGSM'=>'3620{n}@sms.pgsm.hu',
        ),
    'IS'=>array(    /* Iceland */
        'OgVodafone'=>'{n}@sms.is',
        'Siminn'=>'{n}@box.is',
        ),
    'IN'=>array(    /* India */
        'Andhra Pradesh AirTel'=>'91{n}@airtelap.com',
        'Andhra Pradesh Idea Cellular'=>'9848{n}@ideacellular.net',
        'BPL mobile'=>'{n}@bplmobile.com',
        'Chennai Skycell / Airtel'=>'919840{n}@airtelchennai.com',
        'Chennai RPG Cellular'=>'9841{n}@rpgmail.net',
        'Delhi Airtel'=>'919810{n}@airtelmail.com',
        'Delhi Hutch'=>'9811{n}@delhi.hutch.co.in',
        'Gujarat Idea Cellular'=>'9824{n}@ideacellular.net',
        'Gujarat Airtel'=>'919898{n}@airtelmail.com',
        'Gujarat Celforce / Fascel'=>'9825{n}@celforce.com',
        'Goa Airtel'=>'919890{n}@airtelmail.com',
        'Goa BPL Mobile'=>'9823{n}@bplmobile.com',
        'Goa Idea Cellular'=>'9822{n}@ideacellular.net',
        'Haryana Airtel'=>'919896{n}@airtelmail.com',
        'Haryana Escotel'=>'9812{n}@escotelmobile.com',
        'Himachal Pradesh Airtel'=>'919816{n}@airtelmail.com',
        'Idea Cellular'=>'{n}@ideacellular.net',
        'Karnataka Airtel'=>'919845{n}@airtelkk.com',
        'Kerala Airtel'=>'919895{n}@airtelkerala.com',
        'Kerala Escotel'=>'9847{n}@escotelmobile.com',
        'Kerala BPL Mobile'=>'9846{n}@bplmobile.com',
        'Kolkata Airtel'=>'919831{n}@airtelkol.com',
        'Madhya Pradesh Airtel'=>'919893{n}@airtelmail.com',
        'Maharashtra Airtel'=>'919890{n}@airtelmail.com',
        'Maharashtra BPL Mobile'=>'9823{n}@bplmobile.com',
        'Maharashtra Idea Cellular'=>'9822{n}@ideacellular.net',
        'Mumbai Airtel'=>'919892{n}@airtelmail.com',
        'Mumbai BPL Mobile'=>'9821{n}@bplmobile.com',
        'Orange'=>'{n}@orangemail.co.in',
        'Punjab Airtel'=>'919815{n}@airtelmail.com',
        'Pondicherry BPL Mobile'=>'9843{n}@bplmobile.com',
        'Tamil Nadu Airtel'=>'919894{n}@airtelmail.com',
        'Tamil Nadu BPL Mobile'=>'919843{n}@bplmobile.com',
        'Tamil Nadu Aircel'=>'9842{n}@airsms.com',
        'Uttar Pradesh West Escotel'=>'9837{n}@escotelmobile.com',
        ),
    'IE'=>array(    /* Ireland */
        'Meteor'=>'{n}@sms.mymeteor.ie',
        'Meteor MMS'=>'{n}@mms.mymeteor.ie',
        ),
    'IT'=>array(    /* Italy */
        'Telecom Italia Mobile'=>'33{n}@posta.tim.it',
        'Vodafone'=>'{n}@sms.vodafone.it',
        'Vodafone Omnitel'=>'34{n}@vizzavi.it',
        ),
    'JP'=>array(    /* Japan */
        'AU by KDDI'=>'{n}@ezweb.ne.jp',
        'NTT DoCoMo'=>'{n}@docomo.ne.jp',
        'Vodafone Chuugoku/Western'=>'{n}@n.vodafone.ne.jp',
        'Vodafone Hokkaido'=>'{n}@d.vodafone.ne.jp',
        'Vodafone Hokuriko/Central North'=>'{n}@r.vodafone.ne.jp',
        'Vodafone Kansai/West, including Osaka'=>'{n}@k.vodafone.ne.jp',
        'Vodafone Kanto/Koushin/East, including Tokyo'=>'{n}@t.vodafone.ne.jp',
        'Vodafone Kyuushu/Okinawa'=>'{n}@q.vodafone.ne.jp',
        'Vodafone Shikoku'=>'{n}@s.vodafone.ne.jp',
        'Vodafone Touhoku/Niigata/North'=>'{n}@h.vodafone.ne.jp',
        'Vodafone Toukai/Central'=>'{n}@c.vodafone.ne.jp',
        'Willcom'=>'{n}@pdx.ne.jp',
        'Willcom di'=>'{n}@di.pdx.ne.jp',
        'Willcom dj'=>'{n}@dj.pdx.ne.jp',
        'Willcom dk'=>'{n}@dk.pdx.ne.jp',
        ),
    'LV'=>array(    /* Latvia */
        'Kyivstar'=>'{n}@smsmail.lmt.lv',
        'LMT'=>'9{n}@smsmail.lmt.lv',
        'Tele2'=>'{n}@sms.tele2.lv',
        ),
    'LB'=>array(    /* Lebanon */
        'Cellis / LibanCell'=>'9613{n}@ens.jinny.com.lb',
        ),
    'LU'=>array(    /* Luxembourg */
        'P&amp;T Luxembourg'=>'{n}@sms.luxgsm.lu',
        ),
    'MY'=>array(    /* Malaysia */
        'Celcom'=>'019{n}@sms.celcom.com.my',
        ),
    'MU'=>array(    /* Mauritius */
        'Emtel'=>'{n}@emtelworld.net',
        ),
    'MX'=>array(    /* Mexico */
        'Iusacell'=>'{n}@rek2.com.mx',
        ),
    'NI'=>array(    /* Nicaragua */
        'Claro'=>'{n}@ideasclaro-ca.com',
        ),
    'NP'=>array(    /* Nepal */
        'Mero Mobile'=>'{n}@sms.spicenepal.com',
        ),
    'NL'=>array(    /* Netherlands */
        'Dutchtone / Orange-NL'=>'{n}@sms.orange.nl',
        'T-Mobile'=>'31{n}@gin.nl',
        ),
    'NO'=>array(    /* Norway */
        'Netcom'=>'{n}@sms.netcom.no',
        'Telenor'=>'{n}@mobilpost.no',
        ),
    'PA'=>array(    /* Panama */
        'Cable and Wireless'=>'{n}@cwmovil.com',
        ),
    'PL'=>array(    /* Poland */
        'Orange Polska'=>'{n}@orange.pl',
        'Plus GSM'=>'+4860{n}@text.plusgsm.pl',
        ),
    'PT'=>array(    /* Portugal */
        'Telcel'=>'91{n}@sms.telecel.pt',
        'Optimus'=>'93{n}@sms.optimus.pt',
        'TMN'=>'96{n}@mail.tmn.pt',
        ),
    'RU'=>array(    /* Russia */
        'BeeLine GSM'=>'{n}@sms.beemail.ru',
        'MTS'=>'7{n}x@sms.mts.ru',
        'Personal Communication'=>'sms@pcom.ru (number in subject line)',
        'Primtel'=>'{n}@sms.primtel.ru',
        'SCS-900'=>'{n}@scs-900.ru',
        'Uraltel'=>'{n}@sms.uraltel.ru',
        'Vessotel'=>'{n}@pager.irkutsk.ru',
        'YCC'=>'{n}@sms.ycc.ru',
        ),
    'CS'=>array(    /* Serbia and Montenegro */
        'Mobtel Srbija'=>'{n}@mobtel.co.yu',
        ),
    'SG'=>array(    /* Singapore */
        'M1'=>'{n}@m1.com.sg',
        ),
    'SI'=>array(    /* Slovenia */
        'Mobitel'=>'{n}@linux.mobitel.si',
        'Si Mobil'=>'{n}@simobil.net',
        ),
    'ZA'=>array(    /* South Africa */
        'MTN'=>'{n}@sms.co.za',
        'Vodacom'=>'{n}@voda.co.za',
        ),
    'ES'=>array(    /* Spain */
        'Telefonica Movistar'=>'{n}@movistar.net',
        'Vodafone'=>'{n}@vodafone.es',
        ),
    'LK'=>array(    /* Sri Lanka */
        'Mobitel'=>'{n}@sms.mobitel.lk',
        ),
    'SE'=>array(    /* Sweden */
        'Comviq GSM'=>'467{n}@sms.comviq.se',
        'Europolitan'=>'4670{n}@europolitan.se',
        'Tele2'=>'0{n}@sms.tele2.se',
        ),
    'CH'=>array(    /* Switzerland */
        'Sunrise Mobile'=>'{n}@freesurf.ch',
        'Sunrise Mobile'=>'{n}@mysunrise.ch',
        'Swisscom'=>'{n}@bluewin.ch',
        ),
    'TZ'=>array(    /* Tanzania */
        'Mobitel'=>'{n}@sms.co.tz',
        ),
    'UA'=>array(    /* Ukraine */
        'Golden Telecom'=>'{n}@sms.goldentele.com',
        'Kyivstar'=>'{n}x@2sms.kyivstar.net',
        'UMC'=>'{n}@sms.umc.com.ua',
        ),
    'GB'=>array(    /* United Kingdom */
        'BigRedGiant Mobile'=>'{n}@tachyonsms.co.uk',
        'O2'=>'44{n}@mobile.celloneusa.com',
        'O2 (M-mail)'=>'44{n}@mmail.co.uk',
        'Orange'=>'0{n}@orange.net',
        'T-Mobile'=>'0{n}@t-mobile.uk.net',
        'Virgin Mobile'=>'0{n}@vxtras.com',
        'Vodafone'=>'0{n}@vodafone.net',
        ),
    );

    /**
     * @var Zend_Mail
     */
    protected $mail;

    protected $country;
    protected $provider;
    protected $target;
    protected $targetEmail;

    public function __construct(Array $options = null)
    {
        if (isset($options['mailFrom'])) {
            $this->getMail()->setFrom($options['mailFrom']);
        }
        if (isset($options['mailReplyTo'])) {
            $this->getMail()->setReplyTo($options['mailReplyTo']);
        }
    }

    public static function getType()
    {
        return self::TYPE;
    }

    protected function setUserSettings(User $user)
    {
        try {
            $this->setCountry($user->getCountry())
                 ->setProvider($user->getProvider());
        } catch (Sms_Backend_Exception $e) {
            if (!$user->getSmsaddr()) {
                throw new Sms_Backend_Exception('Missing SMS address.');
            }
            $this->setTargetEmail($user->getSmsaddr());
        }
    }

    /**
     * @return array
     */
    public static function getCountries()
    {
        return array_keys(self::$smslist);
    }

    /**
     * @param string $country
     * @return array
     */
    public static function getProviders($country)
    {
        if (!isset(self::$smslist[$country])) {
            return false;
        }
        return array_keys(self::$smslist[$country]);
    }

    /**
     * @param Zend_Mail $mail
     * @return Sms_Backend_Mail
     */
    public function setMail(Zend_Mail $mail)
    {
        $this->mail = $mail;
        return $this;
    }

    /**
     * @return Zend_Mail
     */
    public function getMail()
    {
        if ($this->mail === null) {
            $this->mail = new Zend_Mail();
        }
        return $this->mail;
    }

    /**
     * @param string $country
     * @return Sms_Backend_Email
     * @throws Sms_Backend_Exception
     */
    public function setCountry($country)
    {
        if (!in_array($country, self::getCountries())) {
            throw new Sms_Backend_Exception('Invalid country.');
        }
        $this->country = $country;
        return $this;
    }

    /**
     * Sets provider and target
     *
     * @param string $provider
     * @return Sms_Backend_Email
     * @throws Sms_Backend_Exception
     */
    public function setProvider($provider)
    {
        if (!isset($this->country)) {
            throw new Sms_Backend_Exception('Missing country. Set country first.');
        }
        if (!in_array($provider, self::getProviders($this->country))) {
            throw new Sms_Backend_Exception('Invalid provider.');
        }
        $this->provider = $provider;
        $this->target   = self::$smslist[$this->country][$this->provider];
        return $this;
    }

    public function setTargetEmail($targetEmail)
    {
        $this->targetEmail = $targetEmail;
        return $this;
    }

    /**
     * Sends a message.
     *
     * @param Sms_Message $message
     * @throws Sms_Backend_Exception
     * @return Sms_Backend_Email
     */
    public function send(Sms_Message $message)
    {
        if ($user = $message->getUser()) {
            $this->setUserSettings($user);
        }
        if ($this->target === null && $this->targetEmail === null) {
            throw new Sms_Backend_Exception('Missing target.');
        }
        if ($this->targetEmail === null) {
            if (!$message->getPhoneNumber()) {
                throw new Sms_Backend_Exception('Missing phone number.');
            }
            $targetEmail = str_replace('{n}', $message->getPhoneNumber(), $this->target);
        } else {
            $targetEmail = $this->targetEmail;
        }
        $mail = $this->getMail();
        $mail->addTo($targetEmail)
             ->setSubject($message->getSubject())
             ->setBodyText($message->getMessage())
             ->send();
        return $this;
    }
}
