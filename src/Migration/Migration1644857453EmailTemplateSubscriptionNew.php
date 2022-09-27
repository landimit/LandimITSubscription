<?php declare(strict_types=1);

namespace LandimIT\Subscription\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1644857453EmailTemplateSubscriptionNew extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1644857453;
    }

    public function update(Connection $connection): void
    {
        $mailTemplateTypeId = $this->createMailTemplateType($connection);

        $this->createMailTemplateNew($connection, $mailTemplateTypeId);
    }


    public function updateDestructive(Connection $connection): void
    {

    }


    private function createMailTemplateType(Connection $connection): string
    {
        $mailTemplateTypeId = Uuid::randomHex();

        $enLangId = $this->getLanguageIdByLocale($connection, 'en-GB');
        $deLangId = $this->getLanguageIdByLocale($connection, 'de-DE');

        $englishName = 'Subscription mail template type';
        $germanName = 'Typ der Abonnement-E-Mail-Vorlage';

        $connection->insert('mail_template_type', [
            'id' => Uuid::fromHexToBytes($mailTemplateTypeId),
            'technical_name' => 'subscription_mail_template_type',
            'available_entities' => json_encode(['subscription' => 'subscription']),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        $connection->insert('mail_template_type_translation', [
            'mail_template_type_id' => Uuid::fromHexToBytes($mailTemplateTypeId),
            'language_id' => $enLangId,
            'name' => $englishName,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

     

        $connection->insert('mail_template_type_translation', [
            'mail_template_type_id' => Uuid::fromHexToBytes($mailTemplateTypeId),
            'language_id' => $deLangId,
            'name' => $germanName,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        return $mailTemplateTypeId;
    }


    private function createMailTemplateNew(Connection $connection, string $mailTemplateTypeId): void
    {
        $mailTemplateId = Uuid::randomHex();

        $enLangId = $this->getLanguageIdByLocale($connection, 'en-GB');
        $deLangId = $this->getLanguageIdByLocale($connection, 'de-DE');

        $connection->insert('mail_template', [
            'id' => Uuid::fromHexToBytes($mailTemplateId),
            'mail_template_type_id' => Uuid::fromHexToBytes($mailTemplateTypeId),
            'system_default' => 0,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        $connection->insert('mail_template_translation', [
            'mail_template_id' => Uuid::fromHexToBytes($mailTemplateId),
            'language_id' => $enLangId,
            'sender_name' => '{{ salesChannel.name }}',
            'subject' => 'Your subscription has been confirmed.',
            'description' => 'Your subscription has been confirmed.',
            'content_html' => $this->getContentHtmlEnNew(),
            'content_plain' => $this->getContentPlainEnNew(),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
        

        $connection->insert('mail_template_translation', [
            'mail_template_id' => Uuid::fromHexToBytes($mailTemplateId),
            'language_id' => $deLangId,
            'sender_name' => '{{ salesChannel.name }}',
            'subject' => 'Ihr Abonnement wurde bestätigt.',
            'description' => 'Ihr Abonnement wurde bestätigt.',
            'content_html' => $this->getContentHtmlDeNew(),
            'content_plain' => $this->getContentPlainDeNew(),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }

    private function getLanguageIdByLocale(Connection $connection, string $locale): ?string
    {
        $sql = <<<SQL
SELECT `language`.`id`
FROM `language`
INNER JOIN `locale` ON `locale`.`id` = `language`.`locale_id`
WHERE `locale`.`code` = :code
SQL;

        $languageId = $connection->executeQuery($sql, ['code' => $locale])->fetchColumn();
        if (!$languageId && $locale !== 'en-GB') {
            return null;
        }

        if (!$languageId) {
            return Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM);
        }

        return $languageId;
    }





    /*
    *
    * EMAIL subscription NEW!
    *
    */ 

    private function getContentHtmlEnNew(): string
    {
        return <<<MAIL
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>Space</title>
        
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta  name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0," /> 
        
        <link href="https://fonts.googleapis.com/css?family=Signika:400,600,700" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700" rel="stylesheet" />

        <style type="text/css">


            html { width: 100%; }
            body {margin:0; padding:0; width:100%; -webkit-text-size-adjust:none; -ms-text-size-adjust:none;}
            img {display:block !important; border:0; -ms-interpolation-mode:bicubic;}

            .ReadMsgBody { width: 100%;}
            .ExternalClass {width: 100%;}
            .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div { line-height: 100%; }
            
            .Heading {font-family:'Signika', Arial, Helvetica Neue, Helvetica, sans-serif !important;}
            .MsoNormal {font-family:'Open Sans', Arial, Helvetica Neue, Helvetica, sans-serif !important;}
            p {margin:0 !important; padding:0 !important;}
            
            .images {display:block !important; width:100% !important;}
            .display-button td, .display-button a {font-family:'Open Sans', Arial, Helvetica Neue, Helvetica, sans-serif !important;}

            .display-button a:hover {text-decoration:none !important;}

            
            /* MEDIA QUIRES */
            @media only screen and (max-width:680px)
            {
                table[class=display-width-main] {width:100% !important;}
            }
            
            @media only screen and (max-width:639px)
            {
                body {width:auto !important;}
                table[class=display-width], .display-width {width:100% !important;}
                td[class="hide-height"] {display:none !important;}              
                .text-center{ text-align:center !important; }
                .text-padding { padding:10px 0 !important; }
                .res-height {height:60px !important;}
                .height40 {height:40px !important;}
                .width30 {width:30px !important;}
                td[class="height40"] {height:40px !important;}
                td[class="height20"] {height:20px !important;}
                .res-padding { padding:0 20px !important; } 
                table[class=other-width] {width:300px !important;}
                td[class=menu-height] { height:30px !important; }
                .price{width:288px !important;}
                .border-hide {border-right:0 !important;}
                .border-hide1 {border-bottom:0 !important;}
            }

            @media only screen and (max-width:480px)
            {
                table[class=display-width] table {width:100% !important;}
                table[class=display-width] .button-width .display-button {width:auto !important;}
                td[class=menu-height] { height:30px !important; }
                td[class="hide-height"] {display:none !important;}  
                table[class=display-width] .price{width:288px !important;}
                table[class=display-width] .header1{width:85% !important;}
            }
            
            @media only screen and (max-width:350px)
            {
                
                table[class=display-width] .price{width:100% !important;}
                td[class=menu-height] { height:30px !important; }
                td[class="hide-height"] {display:none !important;}
            }
            
            
            @media only screen and (max-width:425px)
            {
                table[class=display-width] .header1{width:85% !important;}
            }
            
            
            
            
        </style>
    </head>
    <body>
    
            

        <!-- MENU STARTS -->        
        <table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" width="100%">            
            <tr>
                <td align="center">
                    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td align="center" class="res-padding"> 
                                <table align="center" border="0" cellpadding="0" cellspacing="0" class="display-width" width="600">
                                    <tr>
                                        <td height="10" class="menu-height"></td>
                                    </tr>   
                                    <tr>
                                        <td>  

                                            <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" class="display-width" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                <tr>
                                                    <td align="center" valign="middle">
                                                        <a href="#" style="color:#666666;text-decoration:none;">
                                                            <img src="{{ asset('bundles/landimitsubscription/storefront/img/email/logo.png') }}" alt="150x50" width="150" style="margin:0; border:0; padding:0; display:block; border-radius:3px;" />
                                                        </a>
                                                    </td>
                                                </tr>                                               
                                            </table>


                                        </td>
                                    </tr>
                                    <tr>
                                        <td height="10" class="menu-height"></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>           
        </table>
        <!-- MENU ENDS -->
        
        <!-- HEADER STARTS -->
        <table align="center" bgcolor="#c3c3c3" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tbody>
                <tr>
                    <td align="center">
                        <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tbody> 
                                <tr>
                                    <td align="center">
                                        <!--[if gte mso 9]>
                                        <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="mso-width-percent:979; height:468px; margin:auto;">
                                        <v:fill type="tile" src="{{ asset('bundles/landimitsubscription/storefront/img/email/2.jpg') }}" color="#f6f8f7" />
                                        <v:textbox inset="0,0,0,0">
                                        <![endif]-->
                                        <div style="margin:auto;">
                                            <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-image:url({{ asset('bundles/landimitsubscription/storefront/img/email/2.jpg') }}); background-position:center; background-repeat:no-repeat;">
                                                <tbody> 
                                                    <tr>
                                                        <td align="center" class="res-padding">
                                                            <table align="center" border="0" cellpadding="0" cellspacing="0" class="display-width" width="600">
                                                                <tbody>
                                                                    <tr>
                                                                        <td align="left">
                                                                            <table align="left" bgcolor="#c46414" border="0" cellpadding="0" cellspacing="0" class="header1" width="60%">
                                                                                <tbody>
                                                                                    <tr>
                                                                                        <td height="120"></td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            <table align="center" border="0" cellpadding="0" cellspacing="0" width="85%" style="width:85% !important;">
                                                                                                <tbody>
                                                                                                    <tr>
                                                                                                        <td align="left" class="Heading" style="color:#ffffff; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-weight:700; text-transform:uppercase; font-size:14px; line-height:24px; letter-spacing:1px;">
                                                                                                            Welcome to LandimIT.
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                    <tr>
                                                                                                        <td height="5"></td>
                                                                                                    </tr>
                                                                                                    
                                                                                                    <tr>
                                                                                                        <td height="15"></td>
                                                                                                    </tr>
                                                                                                    <tr>
                                                                                                        <td align="left" class="Heading" style="color:#ffffff; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-weight:700; text-transform:uppercase; font-size:30px; line-height:40px; letter-spacing:1px;">
                                                                                                            Nice to have you on board!
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                    <tr>
                                                                                                        <td height="15"></td>
                                                                                                    </tr>
                                                                                                    <tr>        
                                                                                                        <td align="left" class="MsoNormal" style="color:#ffffff; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-size:14px; line-height:24px; letter-spacing:1px;">
                                                                                                            Your subscription has been confirmed. 
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                    
                                                                                                </tbody>    
                                                                                            </table>
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td height="120"></td>
                                                                                    </tr>
                                                                                </tbody>    
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </tbody>    
                                            </table>
                                        </div>
                                        <!--[if gte mso 9]> </v:textbox> </v:rect> <![endif]-->
                                    </td>
                                </tr>
                            </tbody>
                        </table>    
                    </td>
                </tr>                   
            </tbody>    
        </table>
        <!-- HEADER ENDS -->
        
        <!-- OUR-ARTICLES STARTS -->
        <table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tbody>
                <tr>
                    <td align="center">
                        <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tbody> 
                                <tr>
                                    <td height="60"></td>
                                </tr>
                                <tr>
                                    <td align="center" class="res-padding">
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="display-width" width="600">
                                            <tbody>
                                                <tr>
                                                    <td align="center" class="Heading" style="color:#333333; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-weight:600; text-transform:uppercase; font-size:25px; line-height:35px; letter-spacing:1px;">
                                                        Subscription Summary
                                                    </td>
                                                </tr>

                                                
                                                <tr>
                                                    <td height="40"></td>
                                                </tr>   
                                                {% for lineItem in subscription.lineItems %}

                                                <tr>
                                                    <td style="border: 1px solid #ccc;">   
                                                        <!-- TABLE LEFT -->                                                         
                                                        
                                                        <table align="left" border="0" cellpadding="0" cellspacing="0" class="display-width" width="30%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                            <tr>
                                                                <td align="left">
                                                                    <table align="left" border="0" cellpadding="0" cellspacing="0" style="width:auto !important;">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td align="left" width="150">
                                                                                    <img src="{{ lineItem.product.cover.media.url }}" alt="282x210x2" width="282" style="color:#333333; border:0; margin:0; padding:0; border-radius:5px; width:100%; height:auto;" />
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>    
                                                                    </table>
                                                                </td>
                                                            </tr>                                   
                                                        </table>
                                                        
                                                         <table align="left" border="0" cellpadding="0" cellspacing="0" class="display-width" width="1" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                            <tbody>
                                                                <tr>
                                                                    <td height="20" width="1"></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                        
                                                        <!-- TABLE RIGHT -->
                                                        
                                                        <table align="right" border="0" cellpadding="0" cellspacing="0" class="display-width" width="68%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                            <tr>
                                                                <td align="center">
                                                                    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                                                                        <tr>
                                                                            <td height="8" class="hide-height"></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td align="left" class="Heading" style="color:#333333; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-weight:600; font-size:14px; line-height:24px; letter-spacing:1px;padding:15px">
                                                                                {{ lineItem.quantity }}x {{ lineItem.product.translated.name }}
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td height="1"></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td align="left" class="MsoNormal" style="color:#666666; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-size:14px; line-height:24px;">
                                                                                <!-- Donec varius sodales orci. Class aptent taciti sociosqu ad litora torquent per conubia nostra, inceptos End of content.  -->
                                                                            </td>
                                                                        </tr>

                                                                        <tr>
                                                                            <td align="left" class="MsoNormal" style="color:#c46414; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-weight:600; font-size:14px; letter-spacing:1px;padding:15px">
                                                                                <span  style="color:#c46414; text-decoration:none;">{{ lineItem.unitPrice|currency(subscription.currency.isoCode)}}</span>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td height="8" class="hide-height"></td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td height="40"></td>
                                                </tr>   
                                                {% endfor %}
        
                                            </tbody>    
                                        </table>
                                    </td>
                                </tr>
                                
                            </tbody>    
                        </table>
                    </td>
                </tr>                   
            </tbody>    
        </table>
        <!-- OUR-ARTICLES ENDS -->



        <!-- OUR PRICES-1 STARTS -->
        <table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tbody>
                <tr>
                    <td align="center">
                        <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tbody> 
                                <tr>
                                    <td height="60"></td>
                                </tr>
                                <tr>
                                    <td align="center" class="res-padding">
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="display-width" width="600">
                                            <tbody>
                                                
                                                <tr>
                                                    <td>  
                                                        <!-- TABLE LEFT -->                                                         
                                                        
                                                        <table align="left" border="0" cellpadding="0" cellspacing="0" class="display-width" width="100%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                            <tr>
                                                                <td align="center">
                                                                    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" class="price">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td align="center">
                                                                                    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; border:1px solid #c46414;">  
                                                                                        <tr>
                                                                                            <td height="30"></td>
                                                                                        </tr>
                                                                                        <tr>    
                                                                                            <td align="center" class="Heading" style="color:#333333; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-weight:700; text-transform:uppercase; font-size:18px; line-height:24px; letter-spacing:1px;">
                                                                                                Total
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td height="20"></td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td align="center">
                                                                                                <table align="center" bgcolor="#c46414" border="0" cellpadding="0" cellspacing="0" width="45%" style="width:125px !important;">
                                                                                                    <tr>
                                                                                                        <td align="center" class="MsoNormal" style="color:#ffffff; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-weight:700; font-size:42px; letter-spacing:1px;padding: 5px 10px;">
                                                                                                            {{ subscription.totalPrice|currency(subscription.currency.isoCode)}}
                                                                                                        </td>                                       
                                                                                                    </tr>
                                                                                                </table>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td height="25"></td>
                                                                                        </tr>
                                                                                    

                                                                                        <tr>
                                                                                            <td height="20"></td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td align="center" class="button-width">    
                                                                                                <table align="center" bgcolor="#fff" border="0" cellpadding="0" cellspacing="0" class="display-button" style="border-radius:50px;">
                                                                                                    <tr>
                                                                                                        <td align="center" class="MsoNormal" style="color:#c46414; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-weight:600; padding:6px 12px; text-transform:uppercase; font-size:13px; letter-spacing:1px;">
                                                                                                        
                                                                                                                {{ ("product.subscriptionsOption" ~ subscription.intervalName)|trans|sw_sanitize }}
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                </table>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td height="30"></td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>    
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    
                                                    </td>
                                                </tr>
                                            </tbody>    
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td height="60" style="border-bottom:1px solid #cccccc;"></td>
                                </tr>   
                            </tbody>    
                        </table>
                    </td>
                </tr>                   
            </tbody>    
        </table>
        <!-- OUR PRICES-1 ENDS -->

        
        

        <!-- FOOTER STARTS -->
        <table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tbody>
                <tr>
                    <td align="center">
                        <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tbody> 
                                <tr>
                                    <td height="60"></td>
                                </tr>
                                <tr>
                                    <td align="center" class="res-padding">
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="display-width" width="600">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                    
                                                        <!-- TABLE LEFT -->
                                                        
                                                        <table align="left" border="0" cellpadding="0" cellspacing="0" class="display-width" width="53%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                            <tr>
                                                                <td align="left">
                                                                    <table align="left" border="0" cellpadding="0" cellspacing="0" class="display-width" width="48%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td align="center">
                                                                                    <table align="center" border="0" cellpadding="0" cellspacing="0" style="width:auto !important;">
                                                                                        <tbody>
                                                                                            <tr>
                                                                                                <td align="left" valign="middle" style="color:#333333;">
                                                                                                    <img src="{{ asset('bundles/landimitsubscription/storefront/img/email/logo2.png') }}" alt="150x50x2" width="150" style="border:0; margin:0; padding:0;" />
                                                                                                </td>
                                                                                            </tr>
                                                                                        </tbody>    
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>    
                                                                    </table>
                                                                    
                                                                    <table align="left" border="0" cellpadding="0"  cellspacing="0" class="display-width" width="1" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                                        <tr>
                                                                            <td height="20" width="1"></td>
                                                                        </tr>
                                                                    </table>
                                                                    
                                                                    <table align="right" border="0" cellpadding="0" cellspacing="0" class="display-width" width="41%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td align="center">
                                                                                    <table align="center" border="0" cellpadding="0" cellspacing="0" style="width:auto !important;">
                                                                                        <tbody>
                                                                                            <tr>
                                                                                                <td>
                                                                                                    <table align="center" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                                                                        <tbody>
                                                                                                            <tr>        
                                                                                                                <td align="left" class="MsoNormal footer-fs" style="color:#666666; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-size:14px; line-height:24px; letter-spacing:1px;">
                                                                                                                    <a href="{{ url }}/Kontakt/" style="color:#666666;">Contact</a>
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                            <tr>
                                                                                                                <td height="2"></td>
                                                                                                            </tr>
                                                                                                            <tr>        
                                                                                                                <td align="left" class="MsoNormal footer-fs" style="color:#666666; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-size:14px; line-height:24px; letter-spacing:1px;">
                                                                                                                    <a href="{{ url }}/Shop-services/Impressum/" style="color:#666666;" >
                                                                                                                         About Us
                                                                                                                    </a>
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                        </tbody>    
                                                                                                    </table>
                                                                                                </td>
                                                                                            </tr>
                                                                                        </tbody>    
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>    
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        
                                                        <table align="left" class="display-width" border="0" cellpadding="0"  cellspacing="0" width="1" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                            <tr>
                                                                <td height="20" width="1"></td>
                                                            </tr>
                                                        </table>
                                                        
                                                        <!-- TABLE RIGHT -->
                                                        
                                                        <table align="right" border="0" class="display-width" cellpadding="0" cellspacing="0" width="43%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                            <tr>
                                                                <td align="right">
                                                                    
                                                                    
                                                                    <table align="left" border="0" cellpadding="0"  cellspacing="0" class="display-width" width="1" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                                        <tr>
                                                                            <td height="20" width="1"></td>
                                                                        </tr>
                                                                    </table>
                                                                    
                                                                    <table align="left" border="0" cellpadding="0" cellspacing="0" class="display-width" width="41%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td align="center">
                                                                                    <table align="center" border="0" cellpadding="0" cellspacing="0" style="width:auto !important;">
                                                                                        <tbody>
                                                                                            <tr>
                                                                                                <td>
                                                                                                    <table align="center" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                                                                        <tbody>
                                                                                                            <tr>        
                                                                                                                <td align="left" class="MsoNormal footer-fs" style="color:#666666; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-size:14px; line-height:24px; letter-spacing:1px;">
                                                                                                                    <a style="color:#666666;" href="{{ url }}/Datenschutz/">Privacy Policy</a>
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                            <tr>
                                                                                                                <td height="2"></td>
                                                                                                            </tr>
                                                                                                            <tr>        
                                                                                                                <td align="left" class="MsoNormal footer-fs" style="color:#666666; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-size:14px; line-height:24px; letter-spacing:1px;">
                                                                                                                    <a href="{{ url }}/agb/" style="color:#666666;">Team Of Use</a>
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                        </tbody>    
                                                                                                    </table>
                                                                                                </td>
                                                                                            </tr>
                                                                                        </tbody>    
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>    
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>   
                                                <tr>
                                                    <td height="30" style="border-bottom:1px solid #cccccc;">&nbsp;</td>
                                                </tr>
                                                <tr>
                                                    <td height="30"></td>
                                                </tr>
                                                <tr>
                                                    <td>  
                                                        <!-- TABLE LEFT -->                                 
                                                        
                                                        <table align="left" border="0" cellpadding="0" cellspacing="0" class="display-width" width="35%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; width:auto;">
                                                            <tbody>                                                 
                                                                <tr>
                                                                    <td align="center" class="MsoNormal" style="color:#666666; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-size:14px; line-height:24px; font-size:14px; line-height:20px; letter-spacing:1px;">
                                                                        &copy; 2022 LandimIT - All Rights Reserved
                                                                    </td>
                                                                </tr>                                       
                                                            </tbody>
                                                        </table>
                                                        
                                                         <table align="left" border="0" cellpadding="0" cellspacing="0" class="display-width" width="1" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                            <tbody>
                                                                <tr>
                                                                    <td height="20" width="1"></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                                        
                                                        <!-- TABLE RIGHT -->
                                                        
                                                        <table align="right" border="0" cellpadding="0" cellspacing="0" class="display-width" width="28%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; width:auto;">
                                                            <tbody>                                                                     
                                                                <tr>
                                                                    <td align="center" class="MsoNormal" style="color:#666666; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-size:14px; line-height:24px; font-size:14px; line-height:20px; letter-spacing:1px;">
                                                                    </td>
                                                                </tr>                                                   
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>    
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td height="60"></td>
                                </tr>   
                            </tbody>    
                        </table>
                    </td>
                </tr>                   
            </tbody>    
        </table>
        <!-- FOOTER ENDS -->
        
    </body>
</html>
MAIL;
    }

    private function getContentPlainEnNew(): string
    {
        return <<<MAIL
Welcome to LandimIT. 

Nice to have you on board!

Your subscription has been successfully been created. You next renew will be on {date}.

MAIL;
    }

    private function getContentHtmlDeNew(): string
    {
        return <<<MAIL
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>Space</title>
        
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta  name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0," /> 
        
        <link href="https://fonts.googleapis.com/css?family=Signika:400,600,700" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700" rel="stylesheet" />

        <style type="text/css">


            html { width: 100%; }
            body {margin:0; padding:0; width:100%; -webkit-text-size-adjust:none; -ms-text-size-adjust:none;}
            img {display:block !important; border:0; -ms-interpolation-mode:bicubic;}

            .ReadMsgBody { width: 100%;}
            .ExternalClass {width: 100%;}
            .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div { line-height: 100%; }
            
            .Heading {font-family:'Signika', Arial, Helvetica Neue, Helvetica, sans-serif !important;}
            .MsoNormal {font-family:'Open Sans', Arial, Helvetica Neue, Helvetica, sans-serif !important;}
            p {margin:0 !important; padding:0 !important;}
            
            .images {display:block !important; width:100% !important;}
            .display-button td, .display-button a {font-family:'Open Sans', Arial, Helvetica Neue, Helvetica, sans-serif !important;}

            .display-button a:hover {text-decoration:none !important;}

            
            /* MEDIA QUIRES */
            @media only screen and (max-width:680px)
            {
                table[class=display-width-main] {width:100% !important;}
            }
            
            @media only screen and (max-width:639px)
            {
                body {width:auto !important;}
                table[class=display-width], .display-width {width:100% !important;}
                td[class="hide-height"] {display:none !important;}              
                .text-center{ text-align:center !important; }
                .text-padding { padding:10px 0 !important; }
                .res-height {height:60px !important;}
                .height40 {height:40px !important;}
                .width30 {width:30px !important;}
                td[class="height40"] {height:40px !important;}
                td[class="height20"] {height:20px !important;}
                .res-padding { padding:0 20px !important; } 
                table[class=other-width] {width:300px !important;}
                td[class=menu-height] { height:30px !important; }
                .price{width:288px !important;}
                .border-hide {border-right:0 !important;}
                .border-hide1 {border-bottom:0 !important;}
            }

            @media only screen and (max-width:480px)
            {
                table[class=display-width] table {width:100% !important;}
                table[class=display-width] .button-width .display-button {width:auto !important;}
                td[class=menu-height] { height:30px !important; }
                td[class="hide-height"] {display:none !important;}  
                table[class=display-width] .price{width:288px !important;}
                table[class=display-width] .header1{width:85% !important;}
            }
            
            @media only screen and (max-width:350px)
            {
                
                table[class=display-width] .price{width:100% !important;}
                td[class=menu-height] { height:30px !important; }
                td[class="hide-height"] {display:none !important;}
            }
            
            
            @media only screen and (max-width:425px)
            {
                table[class=display-width] .header1{width:85% !important;}
            }
            
            
            
            
        </style>
    </head>
    <body>
    
            

        <!-- MENU STARTS -->        
        <table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" width="100%">            
            <tr>
                <td align="center">
                    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td align="center" class="res-padding"> 
                                <table align="center" border="0" cellpadding="0" cellspacing="0" class="display-width" width="600">
                                    <tr>
                                        <td height="10" class="menu-height"></td>
                                    </tr>   
                                    <tr>
                                        <td>  

                                            <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" class="display-width" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                <tr>
                                                    <td align="center" valign="middle">
                                                        <a href="#" style="color:#666666;text-decoration:none;">
                                                            <img src="{{ asset('bundles/landimitsubscription/storefront/img/email/logo.png') }}" alt="150x50" width="150" style="margin:0; border:0; padding:0; display:block; border-radius:3px;" />
                                                        </a>
                                                    </td>
                                                </tr>                                               
                                            </table>


                                        </td>
                                    </tr>
                                    <tr>
                                        <td height="10" class="menu-height"></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>           
        </table>
        <!-- MENU ENDS -->
        
        <!-- HEADER STARTS -->
        <table align="center" bgcolor="#c3c3c3" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tbody>
                <tr>
                    <td align="center">
                        <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tbody> 
                                <tr>
                                    <td align="center">
                                        <!--[if gte mso 9]>
                                        <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="mso-width-percent:979; height:468px; margin:auto;">
                                        <v:fill type="tile" src="{{ asset('bundles/landimitsubscription/storefront/img/email/2.jpg') }}" color="#f6f8f7" />
                                        <v:textbox inset="0,0,0,0">
                                        <![endif]-->
                                        <div style="margin:auto;">
                                            <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-image:url({{ asset('bundles/landimitsubscription/storefront/img/email/2.jpg') }}); background-position:center; background-repeat:no-repeat;">
                                                <tbody> 
                                                    <tr>
                                                        <td align="center" class="res-padding">
                                                            <table align="center" border="0" cellpadding="0" cellspacing="0" class="display-width" width="600">
                                                                <tbody>
                                                                    <tr>
                                                                        <td align="left">
                                                                            <table align="left" bgcolor="#c46414" border="0" cellpadding="0" cellspacing="0" class="header1" width="60%">
                                                                                <tbody>
                                                                                    <tr>
                                                                                        <td height="120"></td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center">
                                                                                            <table align="center" border="0" cellpadding="0" cellspacing="0" width="85%" style="width:85% !important;">
                                                                                                <tbody>
                                                                                                    <tr>
                                                                                                        <td align="left" class="Heading" style="color:#ffffff; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-weight:700; text-transform:uppercase; font-size:14px; line-height:24px; letter-spacing:1px;">
                                                                                                            Willkommen bei LandimIT.
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                    <tr>
                                                                                                        <td height="5"></td>
                                                                                                    </tr>
                                                                                                    
                                                                                                    <tr>
                                                                                                        <td height="15"></td>
                                                                                                    </tr>
                                                                                                    <tr>
                                                                                                        <td align="left" class="Heading" style="color:#ffffff; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-weight:700; text-transform:uppercase; font-size:30px; line-height:40px; letter-spacing:1px;">
                                                                                                            Schön, Sie an Bord zu haben!
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                    <tr>
                                                                                                        <td height="15"></td>
                                                                                                    </tr>
                                                                                                    <tr>        
                                                                                                        <td align="left" class="MsoNormal" style="color:#ffffff; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-size:14px; line-height:24px; letter-spacing:1px;">
                                                                                                            Ihr Abonnement wurde bestätigt.
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                    
                                                                                                </tbody>    
                                                                                            </table>
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td height="120"></td>
                                                                                    </tr>
                                                                                </tbody>    
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </tbody>    
                                            </table>
                                        </div>
                                        <!--[if gte mso 9]> </v:textbox> </v:rect> <![endif]-->
                                    </td>
                                </tr>
                            </tbody>
                        </table>    
                    </td>
                </tr>                   
            </tbody>    
        </table>
        <!-- HEADER ENDS -->
        
        <!-- OUR-ARTICLES STARTS -->
        <table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tbody>
                <tr>
                    <td align="center">
                        <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tbody> 
                                <tr>
                                    <td height="60"></td>
                                </tr>
                                <tr>
                                    <td align="center" class="res-padding">
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="display-width" width="600">
                                            <tbody>
                                                <tr>
                                                    <td align="center" class="Heading" style="color:#333333; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-weight:600; text-transform:uppercase; font-size:25px; line-height:35px; letter-spacing:1px;">
                                                        Zusammenfassung des Abonnements
                                                    </td>
                                                </tr>

                                                
                                                <tr>
                                                    <td height="40"></td>
                                                </tr>   
                                                {% for lineItem in subscription.lineItems %}

                                                <tr>
                                                    <td style="border: 1px solid #c46414;">  
                                                        <!-- TABLE LEFT -->                                                         
                                                        
                                                        <table align="left" border="0" cellpadding="0" cellspacing="0" class="display-width" width="30%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                            <tr>
                                                                <td align="left">
                                                                    <table align="left" border="0" cellpadding="0" cellspacing="0" style="width:auto !important;">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td align="left" width="282">
                                                                                    <img src="{{ lineItem.product.cover.media.url }}" alt="282x210x2" width="150" style="color:#333333; border:0; margin:0; padding:0; border-radius:5px; width:100%; height:auto;" />
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>    
                                                                    </table>
                                                                </td>
                                                            </tr>                                   
                                                        </table>
                                                        
                                                         <table align="left" border="0" cellpadding="0" cellspacing="0" class="display-width" width="1" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                            <tbody>
                                                                <tr>
                                                                    <td height="20" width="1"></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                        
                                                        <!-- TABLE RIGHT -->
                                                        
                                                        <table align="right" border="0" cellpadding="0" cellspacing="0" class="display-width" width="68%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                            <tr>
                                                                <td align="center">
                                                                    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                                                                        <tr>
                                                                            <td height="8" class="hide-height"></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td align="left" class="Heading" style="color:#333333; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-weight:600; font-size:14px; line-height:24px; letter-spacing:1px;padding:15px">
                                                                                {{ lineItem.quantity }}x {{ lineItem.product.translated.name }}
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td height="1"></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td align="left" class="MsoNormal" style="color:#666666; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-size:14px; line-height:24px;">
                                                                                <!-- Donec varius sodales orci. Class aptent taciti sociosqu ad litora torquent per conubia nostra, inceptos End of content.  -->
                                                                            </td>
                                                                        </tr>

                                                                        <tr>
                                                                            <td align="left" class="MsoNormal" style="color:#c46414; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-weight:600; font-size:14px; letter-spacing:1px;padding:15px">
                                                                                <span  style="color:#c46414; text-decoration:none;">{{ lineItem.unitPrice|currency(subscription.currency.isoCode)}}</span>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td height="8" class="hide-height"></td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td height="40"></td>
                                                </tr>   
                                                {% endfor %}
        
                                            </tbody>    
                                        </table>
                                    </td>
                                </tr>
                                
                            </tbody>    
                        </table>
                    </td>
                </tr>                   
            </tbody>    
        </table>
        <!-- OUR-ARTICLES ENDS -->



        <!-- OUR PRICES-1 STARTS -->
        <table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tbody>
                <tr>
                    <td align="center">
                        <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tbody> 
                                <tr>
                                    <td height="60"></td>
                                </tr>
                                <tr>
                                    <td align="center" class="res-padding">
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="display-width" width="600">
                                            <tbody>
                                                
                                                <tr>
                                                    <td>  
                                                        <!-- TABLE LEFT -->                                                         
                                                        
                                                        <table align="left" border="0" cellpadding="0" cellspacing="0" class="display-width" width="100%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                            <tr>
                                                                <td align="center">
                                                                    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" class="price">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td align="center">
                                                                                    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; border:1px solid #c46414;">  
                                                                                        <tr>
                                                                                            <td height="30"></td>
                                                                                        </tr>
                                                                                        <tr>    
                                                                                            <td align="center" class="Heading" style="color:#333333; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-weight:700; text-transform:uppercase; font-size:18px; line-height:24px; letter-spacing:1px;">
                                                                                                Gesamt
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td height="20"></td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td align="center">
                                                                                                <table align="center" bgcolor="#c46414" border="0" cellpadding="0" cellspacing="0" width="45%" style="width:125px !important;">
                                                                                                    <tr>
                                                                                                        <td align="center" class="MsoNormal" style="color:#ffffff; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-weight:700; font-size:42px; letter-spacing:1px;padding: 5px 10px;">
                                                                                                            {{ subscription.totalPrice|currency(subscription.currency.isoCode)}}
                                                                                                        </td>                                       
                                                                                                    </tr>
                                                                                                </table>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td height="25"></td>
                                                                                        </tr>
                                                                                    

                                                                                        <tr>
                                                                                            <td height="20"></td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td align="center" class="button-width">    
                                                                                                <table align="center" bgcolor="#fff" border="0" cellpadding="0" cellspacing="0" class="display-button" style="border-radius:50px;">
                                                                                                    <tr>
                                                                                                        <td align="center" class="MsoNormal" style="color:#c46414; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-weight:600; padding:6px 12px; text-transform:uppercase; font-size:13px; letter-spacing:1px;">
                                                                                                        
                                                                                                                {{ ("product.subscriptionsOption" ~ subscription.intervalName)|trans|sw_sanitize }}
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                </table>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td height="30"></td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>    
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    
                                                    </td>
                                                </tr>
                                            </tbody>    
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td height="60" style="border-bottom:1px solid #cccccc;"></td>
                                </tr>   
                            </tbody>    
                        </table>
                    </td>
                </tr>                   
            </tbody>    
        </table>
        <!-- OUR PRICES-1 ENDS -->

        
        

        <!-- FOOTER STARTS -->
        <table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tbody>
                <tr>
                    <td align="center">
                        <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tbody> 
                                <tr>
                                    <td height="60"></td>
                                </tr>
                                <tr>
                                    <td align="center" class="res-padding">
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="display-width" width="600">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                    
                                                        <!-- TABLE LEFT -->
                                                        
                                                        <table align="left" border="0" cellpadding="0" cellspacing="0" class="display-width" width="53%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                            <tr>
                                                                <td align="left">
                                                                    <table align="left" border="0" cellpadding="0" cellspacing="0" class="display-width" width="48%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td align="center">
                                                                                    <table align="center" border="0" cellpadding="0" cellspacing="0" style="width:auto !important;">
                                                                                        <tbody>
                                                                                            <tr>
                                                                                                <td align="left" valign="middle" style="color:#333333;">
                                                                                                    <img src="{{ asset('bundles/landimitsubscription/storefront/img/email/logo2.png') }}" alt="150x50x2" width="150" style="border:0; margin:0; padding:0;" />
                                                                                                </td>
                                                                                            </tr>
                                                                                        </tbody>    
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>    
                                                                    </table>
                                                                    
                                                                    <table align="left" border="0" cellpadding="0"  cellspacing="0" class="display-width" width="1" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                                        <tr>
                                                                            <td height="20" width="1"></td>
                                                                        </tr>
                                                                    </table>
                                                                    
                                                                    <table align="right" border="0" cellpadding="0" cellspacing="0" class="display-width" width="41%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td align="center">
                                                                                    <table align="center" border="0" cellpadding="0" cellspacing="0" style="width:auto !important;">
                                                                                        <tbody>
                                                                                            <tr>
                                                                                                <td>
                                                                                                    <table align="center" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                                                                        <tbody>
                                                                                                            <tr>        
                                                                                                                <td align="left" class="MsoNormal footer-fs" style="color:#666666; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-size:14px; line-height:24px; letter-spacing:1px;">
                                                                                                                    <a href="{{ url }}/Kontakt/" style="color:#666666;">Kontakt</a>
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                            <tr>
                                                                                                                <td height="2"></td>
                                                                                                            </tr>
                                                                                                            <tr>        
                                                                                                                <td align="left" class="MsoNormal footer-fs" style="color:#666666; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-size:14px; line-height:24px; letter-spacing:1px;">
                                                                                                                    <a href="{{ url }}/Shop-services/Impressum/" style="color:#666666;" >
                                                                                                                         Über uns
                                                                                                                    </a>
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                        </tbody>    
                                                                                                    </table>
                                                                                                </td>
                                                                                            </tr>
                                                                                        </tbody>    
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>    
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        
                                                        <table align="left" class="display-width" border="0" cellpadding="0"  cellspacing="0" width="1" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                            <tr>
                                                                <td height="20" width="1"></td>
                                                            </tr>
                                                        </table>
                                                        
                                                        <!-- TABLE RIGHT -->
                                                        
                                                        <table align="right" border="0" class="display-width" cellpadding="0" cellspacing="0" width="43%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                            <tr>
                                                                <td align="right">
                                                                    
                                                                    
                                                                    <table align="left" border="0" cellpadding="0"  cellspacing="0" class="display-width" width="1" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                                        <tr>
                                                                            <td height="20" width="1"></td>
                                                                        </tr>
                                                                    </table>
                                                                    
                                                                    <table align="left" border="0" cellpadding="0" cellspacing="0" class="display-width" width="41%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td align="center">
                                                                                    <table align="center" border="0" cellpadding="0" cellspacing="0" style="width:auto !important;">
                                                                                        <tbody>
                                                                                            <tr>
                                                                                                <td>
                                                                                                    <table align="center" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                                                                        <tbody>
                                                                                                            <tr>        
                                                                                                                <td align="left" class="MsoNormal footer-fs" style="color:#666666; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-size:14px; line-height:24px; letter-spacing:1px;">
                                                                                                                    <a style="color:#666666;" href="{{ url }}/Datenschutz/">Datenschutz-Bestimmungen</a>
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                            <tr>
                                                                                                                <td height="2"></td>
                                                                                                            </tr>
                                                                                                            <tr>        
                                                                                                                <td align="left" class="MsoNormal footer-fs" style="color:#666666; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-size:14px; line-height:24px; letter-spacing:1px;">
                                                                                                                    <a href="{{ url }}/agb/" style="color:#666666;">Einsatzteam</a>
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                        </tbody>    
                                                                                                    </table>
                                                                                                </td>
                                                                                            </tr>
                                                                                        </tbody>    
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>    
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>   
                                                <tr>
                                                    <td height="30" style="border-bottom:1px solid #cccccc;">&nbsp;</td>
                                                </tr>
                                                <tr>
                                                    <td height="30"></td>
                                                </tr>
                                                <tr>
                                                    <td>  
                                                        <!-- TABLE LEFT -->                                 
                                                        
                                                        <table align="left" border="0" cellpadding="0" cellspacing="0" class="display-width" width="35%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; width:auto;">
                                                            <tbody>                                                 
                                                                <tr>
                                                                    <td align="center" class="MsoNormal" style="color:#666666; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-size:14px; line-height:24px; font-size:14px; line-height:20px; letter-spacing:1px;">
                                                                        &copy; 2022 LandimIT - All Rights Reserved
                                                                    </td>
                                                                </tr>                                       
                                                            </tbody>
                                                        </table>
                                                        
                                                         <table align="left" border="0" cellpadding="0" cellspacing="0" class="display-width" width="1" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                                                            <tbody>
                                                                <tr>
                                                                    <td height="20" width="1"></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                                        
                                                        <!-- TABLE RIGHT -->
                                                        
                                                        <table align="right" border="0" cellpadding="0" cellspacing="0" class="display-width" width="28%" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; width:auto;">
                                                            <tbody>                                                                     
                                                                <tr>
                                                                    <td align="center" class="MsoNormal" style="color:#666666; font-family:Segoe UI, Helvetica Neue, Arial, Verdana, Trebuchet MS, sans-serif; font-size:14px; line-height:24px; font-size:14px; line-height:20px; letter-spacing:1px;">
                                                                    </td>
                                                                </tr>                                                   
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>    
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td height="60"></td>
                                </tr>   
                            </tbody>    
                        </table>
                    </td>
                </tr>                   
            </tbody>    
        </table>
        <!-- FOOTER ENDS -->
        
    </body>
</html>
MAIL;
    }

    private function getContentPlainDeNew(): string
    {
        return <<<MAIL
Schön, Sie an Bord zu haben!
MAIL;
    }
}