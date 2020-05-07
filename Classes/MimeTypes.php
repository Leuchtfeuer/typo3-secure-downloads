<?php
declare(strict_types = 1);
namespace Leuchtfeuer\SecureDownloads;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

final class MimeTypes
{
    public const ADDITIONAL_MIME_TYPES = [
        // MS-Office filetypes
        'pps' => 'application/vnd.ms-powerpoint',
        'doc' => 'application/msword',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
        'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
        'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
        'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
        'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xps' => 'application/vnd.ms-xpsdocument',

        // Open-Office filetypes
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ott' => 'application/vnd.oasis.opendocument.text-template',
        'odg' => 'application/vnd.oasis.opendocument.graphics',
        'otg' => 'application/vnd.oasis.opendocument.graphics-template',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'otp' => 'application/vnd.oasis.opendocument.presentation-template',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'ots' => 'application/vnd.oasis.opendocument.spreadsheet-template',
        'odc' => 'application/vnd.oasis.opendocument.chart',
        'otc' => 'application/vnd.oasis.opendocument.chart-template',
        'odi' => 'application/vnd.oasis.opendocument.image',
        'oti' => 'application/vnd.oasis.opendocument.image-template',
        'odf' => 'application/vnd.oasis.opendocument.formula',
        'otf' => 'application/vnd.oasis.opendocument.formula-template',
        'odm' => 'application/vnd.oasis.opendocument.text-master',
        'oth' => 'application/vnd.oasis.opendocument.text-web',

        // Media file types
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'jpe' => 'image/jpeg',
        'gif' => 'image/gif',
        'png' => 'image/png',
        'mpeg' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'mpe' => 'video/mpeg',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
        'pdf' => 'application/pdf',
        'svg' => 'image/svg+xml',
        'flv' => 'video/x-flv',
        'swf' => 'application/x-shockwave-flash',
        'htm' => 'text/html',
        'html' => 'text/html',
        'txt' => 'text/plain',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svgz' => 'image/svg+xml',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',
        'rtf' => 'application/rtf',
    ];
}
