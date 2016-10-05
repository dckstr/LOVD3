<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2016-10-04
 * Modified    : 2016-10-04
 * For LOVD    : 3.0-18
 *
 * Copyright   : 2016 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmer  : M. Kroon <m.kroon@lumc.nl>
 *
 *
 * This file is part of LOVD.
 *
 * LOVD is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * LOVD is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with LOVD.  If not, see <http://www.gnu.org/licenses/>.
 *
 *************/

define('ROOT_PATH', '../');
require_once ROOT_PATH . 'inc-init.php';
require_once ROOT_PATH . 'inc-lib-form.php';
require_once ROOT_PATH . 'class/object_transcripts.php';


// Links between LOVD2-LOVD3 fields, with optional conversion function. Format:
// LOVD2_fieldname => array(LOVD3_fieldname, Conversion_function)
// Where LOVD3_fieldname is an LOVD3 field name, optionally with a section
// prefixed from $aImportSections defined below to disambiguate field names
// that occur more than once in the database (e.g. 'vog:created_by').
// Conversion_function is an optional name of a function taking a LOVD2 field
// value as a string as argument and returning LOVD3 field value as a string.
// TODO: complete array with field links
$aFieldLinks = array(
    'Variant/DNA' =>                    array('VariantOnTranscript/DNA'),
    'Variant/RNA' =>                    array('VariantOnTranscript/RNA'),
    'Variant/Protein' =>                array('VariantOnTranscript/Protein'),
    'Variant/DBID' =>                   array('VariantOnGenome/DBID',           'lovd_convertDBID'),
    'Variant/Detection/Template' =>     array('Screening/Template'),
    'Variant/Detection/Technique' =>    array('Screening/Technique'),

    'Patient/Patient_ID' =>             array('Individual/Lab_ID'),
    'Patient/Reference' =>              array('Individual/Reference',           'lovd_convertReference'),

    'ID_pathogenic_' =>                 array('effectid'),
    'ID_status_' =>                     array('statusid'),
    'ID_variant_created_by_' =>         array('vog:created_by',                 'lovd_convertUserID'),
    'variant_created_date_' =>          array('vog:created_date'),
    'ID_variant_edited_by_' =>          array('vog:edited_by',                  'lovd_convertUserID'),
    'variant_edited_date_' =>           array('vog:edited_date'),
    'ID_patient_created_by_' =>         array('individual:created_by',          'lovd_convertUserID'),
    'patient_created_date_' =>          array('individual:created_date'),
    'ID_patient_edited_by_' =>          array('individual:edited_by',           'lovd_convertUserID'),
    'patient_edited_date_' =>           array('individual:edited_date'),
    'ID_patientid_' =>                  array('individual:id',                  'lovd_autoIncIndividualID'),
    'ID_variantid_' =>                  array('vog:id',                         'lovd_autoIncVariantID'),
);


$aImportSections = array(
    'vog' =>        array(
        'prefix' =>           'VariantOnGenome',
        'output_header' =>    'Variants_On_Genome',
        'db_fields' =>        $_DB->query('DESCRIBE ' . TABLE_VARIANTS)->fetchAllColumn()),
    'vot' =>        array(
        'prefix' =>           'VariantOnTranscript',
        'output_header' =>    'Variants_On_Transcripts',
        'db_fields' =>        $_DB->query('DESCRIBE ' . TABLE_VARIANTS_ON_TRANSCRIPTS)->fetchAllColumn()),
    'individual' => array(
        'prefix' =>           'Individual',
        'output_header' =>    'Individuals',
        'db_fields' =>        $_DB->query('DESCRIBE ' . TABLE_INDIVIDUALS)->fetchAllColumn()),
    'i2d' => array(
        'output_header' =>    'Individuals2Diseases'
    )
);





function lovd_autoIncIndividualID ($_LOVD2PatientID)
{
    static $counter;
    if (!isset($counter)) {
        $counter = 1;
    } else {
        $counter++;
    }
    return $counter;
}





function lovd_autoIncVariantlID ($_LOVD2PatientID)
{
    static $counter;
    if (!isset($counter)) {
        $counter = 1;
    } else {
        $counter++;
    }
    return $counter;
}





function lovd_convertDBID ($LOVD2DBID)
{
    // Returns an LOVD3-formatted DBID for the given $LOVD2DBID by padding
    // the number with an extra '0'.
    list($sGene, $sNumber) = explode('_', $LOVD2DBID);
    return $sGene . '_0' . $sNumber;
}






function lovd_convertReference ($LOVD2Reference)
{
    // Convert LOVD2-style reference to LOVD3-style. E.g.:
    // {PMID21228398:Bell 2011} => {PMID:Bell 2011:21228398}
    return preg_replace('/{PMID(\d+):(\w+)}/', '{PMID:\\2:\\1}', $LOVD2Reference);
}





function lovd_convertUserID ($LOVD2UserID)
{
    // TODO: implement lovd_convertUserID()
    return $LOVD2UserID;
}





function lovd_showConversionForm ($nMaxSizeLOVD, $nMaxSize)
{
    // Print HTML for the form specifying input to be converted.
    // Returns nothing.

    global $_T;

    // Page header.
    define('PAGE_TITLE', 'Convert LOVD2 export to LOVD3 import');
    $_T->printHeader(false);
    $_T->printTitle();

    // Show viewlist for searching and selecting a transcript.
    $_DATA = new LOVD_Transcript();
    $_DATA->setRowLink('Transcripts', 'javascript: $("input[name=\'transcriptid\']").val({{ID}}); return false;');
    $_GET['page_size'] = 10;
    $_DATA->viewList('Transcripts', array('ID', 'variants'));

    print('      <FORM action="' . CURRENT_PATH . '?' . ACTION .
        '" method="post" enctype="multipart/form-data">' . "\n");
    lovd_errorPrint();

    $aCharSets = array(
        'auto' => 'Autodetect',
        'UTF-8' => 'UTF-8 / Unicode',
        'ISO-8859-1' => 'ISO-8859-1 / Latin-1');

    // Array which will make up the form table.
    $aForm = array(
        array('POST', '', '', '', '35%', '14', '65%'),
        array('Transcript ID (click in table above)', 'Transcript to which generated import data' .
              ' will be linked.', 'text', 'transcriptid', 10),
        array('', '', 'note', 'Click the transcript in the table above to copy its ID here.'),
        'skip',
        array('Select LOVD2 export file to convert', '', 'file', 'LOVD2_export', 50),
        array('', 'Current file size limits:<BR>LOVD: ' . ($nMaxSizeLOVD/(1024*1024)) .
            'M<BR>PHP (upload_max_filesize): ' . ini_get('upload_max_filesize') .
            '<BR>PHP (post_max_size): ' .
            ini_get('post_max_size'), 'note', 'The maximum file size accepted is ' .
            round($nMaxSize/pow(1024, 2), 1) . ' MB' . ($nMaxSize == $nMaxSizeLOVD? '' :
                ', due to restrictions on this server. If you wish to have it increased, contact' .
                ' the server\'s system administrator') . '.'),
        array('Character encoding of imported file', 'If your file contains special characters ' .
            'like &egrave;, &ouml; or even just fancy quotes like &ldquo; or &rdquo;, LOVD needs ' .
            'to know the file\'s character encoding to ensure the correct display of the data.',
            'select', 'charset', 1, $aCharSets, false, false, false),
        array('', '', 'note', 'Please only change this setting in case you encounter problems ' .
            'with displaying special characters in imported data. Technical information about ' .
            'character encoding can be found <A ' .
            'href="http://en.wikipedia.org/wiki/Character_encoding" target="_blank">on Wikipedia' .
            '</A>.'),
        'hr',
        array('', '', 'submit', 'Generate LOVD3 import file'),
    );
    lovd_viewForm($aForm);

    print('</FORM>' . "\n\n");

    $_T->printFooter();
}




function lovd_validateConversionForm ($aRequest, $aFiles, $zTranscript, $nMaxSize, $nMaxSizeLOVD)
{
    // Validate fields submitted by form generated in lovd_showConversionForm().
    // Returns true if there were no errors.

    if (empty($aRequest['transcriptid'])) {
        lovd_errorAdd('transcriptid', 'No transcript selected.');
    } elseif (empty($zTranscript)) {
        lovd_errorAdd('transcriptid', 'Unknown transcript.');
    }

    if (empty($aFiles['LOVD2_export']) || ($aFiles['LOVD2_export']['error'] > 0 &&
            $aFiles['LOVD2_export']['error'] < 4)) {
        lovd_errorAdd('LOVD2_export', 'There was a problem with the file transfer. Please try ' .
            'again. The file cannot be larger than ' . round($nMaxSize/pow(1024, 2), 1) . ' MB' .
            ($nMaxSize == $nMaxSizeLOVD? '' : ', due to restrictions on this server') . '.');

    } elseif ($aFiles['LOVD2_export']['error'] == 4 || !$aFiles['LOVD2_export']['size']) {
        lovd_errorAdd('LOVD2_export', 'Please select a file to upload.');

    } elseif ($aFiles['LOVD2_export']['size'] > $nMaxSize) {
        lovd_errorAdd('LOVD2_export', 'The file cannot be larger than ' .
            round($nMaxSize/pow(1024, 2), 1) . ' MB' . ($nMaxSize == $nMaxSizeLOVD? '' :
                ', due to restrictions on this server') . '.');

    } elseif ($aFiles['LOVD2_export']['error']) {
        // Various errors available from 4.3.0 or later.
        lovd_errorAdd('LOVD2_export', 'There was an unknown problem with receiving the file ' .
            'properly, possibly because of the current server settings. If the problem persists,' .
            ' please contact the database administrator.');
    }

    return !lovd_error();
}




function lovd_openLOVD2ExportFile($aRequest, $aFiles)
{
    // Returns an array with the contents of the uploaded LOVD2 export file,
    // returns false when something went wrong with opening or decoding the
    // file.
    
    // Find out the MIME-type of the uploaded file. Sometimes
    // mime_content_type() seems to return False. Don't stop processing if
    // that happens.
    // However, when it does report something different, mention what type was
    // found so we can debug it.
    $sType = '';
    if (function_exists('mime_content_type')) {
        $sType = mime_content_type($aFiles['LOVD2_export']['tmp_name']);
    }
    if ($sType && substr($sType, 0, 5) != 'text/') {
        // Not all systems report the regular files as "text/plain"; also
        // reported was "text/x-pascal; charset=us-ascii".
        lovd_errorAdd('LOVD2_export', 'The upload file is not a tab-delimited text file and cannot be ' .
            'imported. It seems to be of type "' . htmlspecialchars($sType) . '".');

    } else {
        $fInput = @fopen($aFiles['LOVD2_export']['tmp_name'], 'r');
        if (!$fInput) {
            lovd_errorAdd('LOVD2_export', 'Cannot open file after it was received by the server.');
        } else {
            // Check mode, we take no default if we don't understand the answer.
            if (empty($aRequest['mode']) || !isset($aModes[$aRequest['mode']])) {
                lovd_errorAdd('mode', 'Please select the import mode from the list of options.');
            }

            // Open the file using file() to check the line endings, then check the encodings, try
            // to use as little memory as possible.
            // Reading the entire file in memory, because we need to detect the encoding and
            // possibly convert.
            $aData = lovd_php_file($aFiles['LOVD2_export']['tmp_name']);

            // Fix encoding problems.
            if ($aRequest['charset'] == 'auto' || !isset($aCharSets[$aRequest['charset']])) {
                // Auto detect charset, it's not given.
                // FIXME; Should we ever allow more encodings?
                $sEncoding = mb_detect_encoding(implode("\n", $aData), array('UTF-8', 'ISO-8859-1'), true);
                if (!$sEncoding) {
                    // Could not be detected.
                    lovd_errorAdd('charset', 'Could not autodetect the file\'s character ' .
                        'encoding. Please select the character encoding from from the list of ' .
                        'options.');
                } elseif ($sEncoding != 'UTF-8') {
                    // Is not UTF-8, and for sure has special chars.
                    return utf8_encode_array($aData);
                }
            } elseif ($aRequest['charset'] == 'ISO-8859-1') {
                return utf8_encode_array($aData);
            }
            return $aData;
        }
    }
    
    return false;
}




function lovd_translateCustomColumn ($sFieldname, $aImportSections)
{
    // Stub for translating a custom column name. E.g. "Variant/Exon" =>
    // VariantOnTranscript/Exon

    if (strpos($sFieldname, '/') === false) {
        // Appears not a custom column.
        return $sFieldname;
    }

    list(, $sFieldnameSuffix) = explode('/', $sFieldname, 2);
    foreach ($aImportSections as $sSection => $aImportSection) {
        // See if any DB field matches the suffix part of the LOVD2 field name.
        foreach ($aImportSection['db_fields'] as $sDBField) {
            if (strpos($sDBField, '/') !== false) {
                list(, $sDBFieldSuffix) = explode('/', $sDBField, 2);
                if ($sFieldnameSuffix == $sDBFieldSuffix) {
                    return $sDBField;
                }
            }
        }
    }

    // No corresponding DB field found.
    // Todo: do something smarter here (e.g. map all Variant/* fields to VOG)
    return $sFieldname;
}




function lovd_getHeaders ($aData, $aFieldLinks, $aImportSections)
{
    // Returns two arrays with field names for the input records. The first
    // array contains the names as defined in the header of the input file. The
    // second array contains corresponding field names for the output file (the
    // LOVD3 import format).
    // Returns false for both arrays if no header can be found.

    if (!is_array($aData)) {
        return array(false, false);
    }

    foreach ($aData as $i => $sLine) {
        $sLine = trim($sLine);
        if (empty($sLine) || $sLine[0] == "#") {
            // Ignore blank lines and comments.
            continue;
        }

        $matches = array();
        preg_match_all('/"{{\s*([^ }]+)\s*}}"/', $sLine, $matches);

        if (empty($matches[0]) || empty($matches[1])) {
            // Cannot find header in first non-empty, non-comment line in file. Show an error.
            break;
        }

        // Return array of field names (matched sub-patterns of regex above).
        $aOutputHeaders = array();
        foreach ($matches[1] as $sInputFieldname) {
            if (isset($aFieldLinks[$sInputFieldname])) {
                $aOutputHeaders[] = $aFieldLinks[$sInputFieldname][0];
            } else {
                $aOutputHeaders[] = lovd_translateCustomColumn($sInputFieldname, $aImportSections);
            }
        }
        return array($matches[1], $aOutputHeaders);
    }

    lovd_errorAdd('LOVD2_export', 'Cannot find header in file.');
    return array(false, false);
}





function lovd_generateImportData($aImportSections, $aOutputHeaders, $aRecords)
{
    // Generate LOVD3 import data format from converted LOVD2 records.

    // Denote what record fields belong to which sections in the generated import data.
    $aSectionHeaderIdxs = array();
    foreach ($aImportSections as $sSection => $aImportSection) {
        // Translate output headers back to normal if they have 'section:' prefix.
        $aSectionHeaders = array_map(function ($sHeader) use ($sSection) {
            return preg_replace('/^' . $sSection . ':/i', '', $sHeader);
        }, $aOutputHeaders);

        // Get overlap between output headers and DB fields of current section.
        $aSectionHeaderIdxs[$sSection] = array_keys(array_intersect($aSectionHeaders,
            $aImportSection['db_fields']));

        // Add leftover fields having prefix corresponding to section.
        $sHeaderPrefix = $aImportSection['prefix'];
        foreach ($aSectionHeaders as $i => $sHeader) {
            if (!in_array($i, $aSectionHeaderIdxs[$sSection]) &&
                substr($sHeader, 0, strlen($sHeaderPrefix)) == $sHeaderPrefix) {
                // Field $i in $aHeaders and $aRecords belongs to section $sSection.
                $aSectionHeaderIdxs[$sSection][] = $i;
            }
        }
    }

    // Generate output records per section.
    $aOut = array();
    foreach ($aRecords as $aRecord) {
        foreach ($aSectionHeaderIdxs as $sSection => $aFieldIndices) {
            $aOutRecord = array();
            foreach ($aFieldIndices as $i) {
                $aOutRecord[] = $aRecord[$i];
            }
            $aOut[$sSection][] = $aOutRecord;
        }
    }

    // Generate and return text output with section headers and output records
    // per section.
    $sOutput = '<pre>' . "\n";
    // Todo: Fix file header (LOVD version and Filter gene name)
    $sOutput .= '### LOVD-version 3000-170 ### Full data download ### To import, do not remove ' .
        "or alter this header ###\n## Filter: (gene = AARS2)\n# charset = UTF-8";
    foreach ($aOut as $sSection => $aOutRecords) {
        $sOutput .= "\n" . '## ' . $aImportSections[$sSection]['output_header'];
        $sOutput .= ' ## Do not remove or alter this header ##' . "\n";

        $aSectionHeaders = array();
        foreach ($aSectionHeaderIdxs[$sSection] as $i) {
            // Add header to output without potential section prefix (e.g. 'vog:').
            $aSectionHeaders[] = '"{{ ' . preg_replace('/^[^:]*:/', '', $aOutputHeaders[$i]) .
                                 ' }}"';
        }
        $sOutput .= join("\t", $aSectionHeaders) . "\n";

        foreach ($aOutRecords as $aOutRecordFields) {
            $sOutput .= join("\t", $aOutRecordFields) . "\n";
        }
    }
    $sOutput .= '</pre>' . "\n";
    return $sOutput;
}






function main ($aFieldLinks, $aImportSections)
{
    global $_DB;

    // Only allow managers or higher to perform conversion.
    lovd_requireAUTH(LEVEL_MANAGER);

    // Selected transcript, will be created from request arguments.
    $zTranscript = null;

    // Determine max file upload size in bytes.
    $nMaxSizeLOVD = 100*1024*1024; // 100MB LOVD limit. Note: value copied from import.php
    $nMaxSize = min($nMaxSizeLOVD,
        lovd_convertIniValueToBytes(ini_get('upload_max_filesize')),
        lovd_convertIniValueToBytes(ini_get('post_max_size')));
    
    if (!POST) {
        // Show file upload form.
        lovd_showConversionForm($nMaxSizeLOVD, $nMaxSize);
        return;
    } else {
        if (!empty($_POST['transcriptid'])) {
            $qTranscript = $_DB->query('SELECT * FROM ' . TABLE_TRANSCRIPTS .
                ' WHERE id=?', array($_POST['transcriptid']));
            $zTranscript = $qTranscript->fetchAssoc();
        }
        if (!lovd_validateConversionForm($_POST, $_FILES, $zTranscript, $nMaxSize,
                                         $nMaxSizeLOVD)) {
            lovd_showConversionForm($nMaxSizeLOVD, $nMaxSize);
            return;
        }
    }

    // TODO: Setup cache of diseases to link 'Patient/Phenotype/Disease' field

    // Free up the session for other requests when parsing the input file.
    session_write_close();
    @set_time_limit(0);

    $aData = lovd_openLOVD2ExportFile($_POST, $_FILES);
    list($aInputHeaders, $aOutputHeaders) = lovd_getHeaders($aData, $aFieldLinks, $aImportSections);
    if ($aData === false || $aInputHeaders === false) {
        lovd_showConversionForm($nMaxSizeLOVD, $nMaxSize);
        return;
    }

    // Array for temporary storing converted data per input line.
    $aRecords = array();
    $aDiseases = array();

    // Array for storing link of unique variant_id/patient_id combination to
    // key in $aRecords. This is used to find homozygous variants as they will
    // occur twice in the LOVD2 export file with identical variant_id/
    //patient_id.
    $aUniqueVarLink = array();

    foreach ($aData as $i => $sLine) {
        // If observed combination of variant_id/patient_id recurs, change allele field in
        // existing record to homozygous

        $sLine = trim($sLine);
        if (empty($sLine) || $sLine[0] == "#" || substr($sLine, 0, 3) == '"{{') {
            // Ignore blank lines, comments and the header line.
            continue;
        }

        // Join field names and field values in an array.
        $aFields = array_map(null, $aInputHeaders, explode("\t", $sLine));
        $aRecord = array();

        foreach ($aFields as $aField) {
            list($sFieldName, $sFieldValue) = $aField;

            if (isset($aFieldLinks[$sFieldName])) {
                if (count($aFieldLinks[$sFieldName]) == 1) {
                    $aRecord[] = $sFieldValue;
                } else {
                    $aRecord[] = call_user_func($aFieldLinks[$sFieldName][1], $sFieldValue);
                }
            } else {
                // Copy field as is.
                $aRecord[] = $sFieldValue;
            }
        }

        $sRecordID = $aRecord[array_search('ID_variantid_', $aInputHeaders)] . '_';
        $sRecordID .= $aRecord[array_search('ID_patient_', $aInputHeaders)];
        if (array_key_exists($sRecordID, $aUniqueVarLink)) {
            // Combination variant_id/patient_id already seen, set previous
            // record allele field to 3 (homozygous). Skip this record.
            $nAlleleFieldIndex = array_search('allele', $aOutputHeaders);
            $aRecords[$aUniqueVarLink[$sRecordID]][$nAlleleFieldIndex] = 3;
            continue;
        } else {
            // Link current variant_id/patient_id combination to current index
            // in $aRecords.
            $aUniqueVarLink[$sRecordID] = count($aRecords);
        }

        $sDiseaseHeader = 'Patient/Phenotype/Disease';
        if (in_array($sDiseaseHeader, $aInputHeaders)) {
            $sDiseaseID = lovd_getDiseaseID($sDiseaseHeader);
        }


        // TODO: Fill with mutalyzer mappingInfo:
        // vog/position_g_start
        // vog/position_g_end
        // vog/type
        // vot/position_c_start
        // vot/position_c_start_intron
        // vot/position_c_end
        // vot/position_c_end_intron

        // TODO: Fill with mutalyzer numberConversion:
        // vog/VariantOnGenome/DNA

        $aRecords[] = $aRecord;
    }

    echo lovd_generateImportData($aImportSections, $aOutputHeaders, $aRecords);
}

main($aFieldLinks, $aImportSections);


