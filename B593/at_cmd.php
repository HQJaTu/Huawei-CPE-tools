<?php

/**
 * Command parser
 * @param $first_char   + or ^
 * @param $command AT-command response to query for
 * @param $args The arguments
 * @return string HTML response
 * @throws RuntimeException on invalid parameters
 */
function QueryResolver($first_char, $command, $args)
{
    $explanation = null;
    switch ($command) {
        case "SETPORT":
            $modes = array(
                1 => '3G MODEM',
                2 => '3G PCUI',
                3 => '3G DIAG',
                5 => '3G GPS',
                'A' => 'BLUE TOOTH',
                16 => 'NCM',
                'A1' => 'CDROM',
                'A2' => 'SD',
                10 => '4G MODEM',
                12 => '4G PCUI',
                13 => '4G DIAG',
                14 => '4G GPS');
            $operating_modes = explode(';', $args);
            if (count($operating_modes) != 2) {
                throw new RuntimeException("I don't know this one!");
            }
            $selected_modes = explode(',', $operating_modes[1]);

            $explanation = "<ol>";
            foreach ($selected_modes as $mode) {
                if (!isset($modes[$mode])) {
                    $explanation .= "<li>$mode: -unknown-</li>";
                } else {
                    $explanation .= "<li>$mode: " . $modes[$mode] . "</li>";
                }
            }
            $explanation .= "</ol>";
            break;

        case "COPS":
            $values = explode(',', $args);
            $explanation = "Current available network and operator information:<ul>";
            for ($idx = 0; isset($values[$idx]); ++$idx) {
                switch ($idx) {
                    case 0:
                        $explanation .= "<li>Network selection mode:<br>\n" . $values[$idx] . " = ";
                        $modes = array('Automatic mode (default value).',
                            'Manual network searching.',
                            'Deregisters a network and maintains the network in unregistered state until <mode>=0, 1, or 4 (this is not supported currently).',
                            'Sets the value of <format> in the result returned by the read command.',
                            'Combination of automatic and manual network searching modes. If manual network searching fails, the automatic network searching mode is started.'
                        );
                        if (!isset($modes[$values[$idx]])) {
                            $explanation .= "-don't-know-";
                        } else {
                            $explanation .= $modes[$values[$idx]];
                        }
                        $explanation .= " </li >";
                        break;
                    case 1:
                        $explanation .= "<li>Network status identifier:<br>\n" . $values[$idx] . " = ";
                        $modes = array('Unknown',
                            'Available',
                            'Currently registered',
                            'Disabled');
                        if (!isset($modes[$values[$idx]])) {
                            $explanation .= "-don't-know-";
                        } else {
                            $explanation .= $modes[$values[$idx]];
                        }
                        $explanation .= " </li >";
                        break;
                    case 2:
                        $explanation .= "<li>PLMN:<br>";
                        if (preg_match('/^"(.+)"$/', $values[$idx], $matches) && strlen($matches[1]) == 5) {
                            $mcc = substr($matches[1], 0, 3);
                            $mnc = substr($matches[1], 3, 2);
                            require_once('plmn_lists.php');
                            if (isset($plmns["$mcc-$mnc"])) {
                                $info = $plmns["$mcc-$mnc"];
                                $explanation .= "$mcc = " . $info[1] . "<br>\n";
                                $explanation .= "$mnc = " . $info[3] . "<br>\n";
                            } else {
                                $explanation .= "MCC = $mcc<br>\n";
                                $explanation .= "MNC = $mnc<br>\n";
                            }
                        } else {
                            $explanation .= $values[$idx];
                        }
                        $explanation .= " </li >";
                        break;
                    case 3:
                        $explanation .= "<li>? = " . $values[$idx];
                        $explanation .= " </li >";
                        break;
                }
            }
            $explanation .= "</ul > ";
            break;

        case "LOCINFO":
            $values = explode(',', $args);
            $explanation = "Current available network and operator information:<ul>";
            for ($idx = 0; isset($values[$idx]); ++$idx) {
                switch ($idx) {
                    case 0:
                        $explanation .= "<li>PLMN:<br>";
                        if (strlen($values[$idx]) == 5) {
                            $mcc = substr($values[$idx], 0, 3);
                            $mnc = substr($values[$idx], 3, 2);
                            require_once('plmn_lists.php');
                            if (isset($plmns["$mcc-$mnc"])) {
                                $info = $plmns["$mcc-$mnc"];
                                $explanation .= "$mcc = " . $info[1] . "<br>\n";
                                $explanation .= "$mnc = " . $info[3] . "<br>\n";
                            } else {
                                $explanation .= "MCC = $mcc<br>\n";
                                $explanation .= "MNC = $mnc<br>\n";
                            }
                        } else {
                            $explanation .= $values[$idx];
                        }
                        $explanation .= " </li >";
                        break;
                    case 1:
                        $lac = hexdec($values[$idx]);
                        $explanation .= "<li>LAC: " . $lac . " (decimal)";
                        $explanation .= " </li >";
                        break;
                    case 2:
                        $explanation .= "<li>? = " . $values[$idx];
                        $explanation .= " </li >";
                        break;
                    case 3:
                        $cellid = hexdec($values[$idx]);
                        $explanation .= "<li>Cell ID: " . $cellid . " (decimal)";
                        $explanation .= " </li >";
                        break;
                }
            }
            $explanation .= "</ul > ";
            break;

        case "CEREG":
            $values = explode(',', $args);
            $explanation = "Current available network and operator information:<ul>";
            for ($idx = 0; isset($values[$idx]); ++$idx) {
                switch ($idx) {
                    case 0:
                        $explanation .= "<li>Setting:<br>\n";
                        $settings = array(
                            'Disable unsolicited result code +CGREG. (default value)',
                            'Enable unsolicited result code +CGREG',
                            'Enable network registration and location information unsolicited result'
                        );
                        if (!isset($settings[$values[$idx]])) {
                            $explanation .= "-don't-know-";
                        } else {
                            $explanation .= $settings[$values[$idx]];
                        }
                        $explanation .= " </li >";
                        break;
                    case 1:
                        $explanation .= "<li>Current status of network registration:<br>\n";
                        $statuses = array(
                            'Does not register. Currently, the ME does not search for a new operator to be registered with.',
                            'Registers with the local network.',
                            'Does not register. The ME, however, is searching for a new operator to be registered with.',
                            'Network registration is rejected.',
                            'Unknown reason.',
                            'Registers with the roaming network.');
                        if (!isset($statuses[$values[$idx]])) {
                            $explanation .= "-don't-know-";
                        } else {
                            $explanation .= $statuses[$values[$idx]];
                        }
                        $explanation .= " </li >";
                        break;
                    case 2:
                        $lac = hexdec($values[$idx]);
                        $explanation .= "<li>LAC: " . $lac . " (decimal)";
                        $explanation .= " </li >";
                        break;
                    case 3:
                        $cellid = hexdec($values[$idx]);
                        $explanation .= "<li>Cell ID: " . $cellid . " (decimal)";
                        $explanation .= " </li >";
                        break;
                }
            }
            $explanation .= "</ul > ";
            break;

        case "CSQ":
            $values = explode(',', $args);
            if (count($values) != 2) {
                throw new RuntimeException("Doesn't look anything I know of!");
            }
            $explanation = "Signal Quality:<ul>";
            $value = -113;
            $rssi = array('≤ ' . $value);
            for ($idx = 1; $idx <= 30; ++$idx) {
                array_push($rssi, $value);
                $value += 2;
            }
            $value += 2;
            array_push($rssi, '≥ ' . $value);
            $explanation .= "<li>RSSI: " . $values[0] . " = ";
            if (!isset($rssi[$values[0]])) {
                $explanation .= "-don't-know-";
            } else {
                $explanation .= $rssi[$values[0]] . " dBm";
            }
            $explanation .= " </li >";
            $explanation .= "<li>bit error rate: " . $values[1] . " = ";
            if ($values[1] == 99) {
                $explanation .= "(not supported currently and only 99 can be displayed)";
            } else {
                $explanation .= "-don't-know-";
            }
            $explanation .= " </li >";
            $explanation .= "</ul > ";
            break;

        case "HCSQ":
            $values = explode(',', $args);
            $explanation = "Signal Strength:<ul>";
            $sysmode = array(
                '"NOSERVICE"' => array('NOSERVICE mode', 0),
                '"GSM"' => array('GSM/GRPS/EDGE mode', 1),
                '"WCDMA"' => array('WCDMA/HSDPA/HSPA mode', 3),
                '"LTE"' => array('LTE mode', 4)
            );
            if (!isset($sysmode[$values[0]])) {
                throw new RuntimeException("Doesn't look a sysmode I know of!");
            }
            $explanation .= "<li>Running in: " . $sysmode[$values[0]][0];
            $explanation .= " </li >";
            if (count($values) != $sysmode[$values[0]][1] + 1) {
                throw new RuntimeException("Not enough parameters for this mode!");
            }
            switch ($values[0]) {
                case '"NOSERVICE"':
                    $explanation .= "<li>-no-signal-quality-</li >";
                    break;
                case '"GSM"':
                    $gsm_rssi = $values[1];

                    // RSSI:
                    $explanation .= "<li>RSSI (received signal strength): $gsm_rssi = ";
                    $explanation .= RSSI_calculator($gsm_rssi);
                    $explanation .= " </li >";
                    break;
                case '"WCDMA"':
                    $wcdma_rssi = $values[1];
                    $wcdma_rscp = $values[2];
                    $wcdma_ecio = $values[3];

                    // RSSI:
                    $explanation .= "<li>RSSI (received signal strength): $wcdma_rssi = ";
                    $explanation .= RSSI_calculator($wcdma_rssi);
                    $explanation .= " </li >";

                    // RSRP:
                    $explanation .= "<li>RSRP (reference signal received power): $wcdma_rscp = ";
                    $explanation .= RSSI_calculator($wcdma_rscp);
                    $explanation .= " </li >";

                    // ECIO:
                    $value = -32.0;
                    $ecio = array('≤ ' . $value);
                    for ($idx = 1; $idx <= 65; ++$idx) {
                        array_push($ecio, $value);
                        $value += 0.5;
                    }
                    $explanation .= "<li>RSRQ (reference signal received quality): $wcdma_ecio = ";
                    if (!isset($ecio[$wcdma_ecio])) {
                        $explanation .= "-don't-know-";
                    } else {
                        $explanation .= $ecio[$wcdma_ecio] . " dB";
                    }
                    $explanation .= " </li >";
                    break;
                case '"LTE"':
                    $lte_rssi = $values[1];
                    $lte_rsrp = $values[2];
                    $lte_sinr = $values[3];
                    $lte_rsrq = $values[4];

                    // RSSI:
                    $explanation .= "<li>RSSI (received signal strength): $lte_rssi = ";
                    $explanation .= RSSI_calculator($lte_rssi);
                    $explanation .= " </li >";

                    // RSRP:
                    $value = -140;
                    $rsrp = array('≤ ' . $value);
                    for ($idx = 1; $idx <= 97; ++$idx) {
                        array_push($rsrp, $value);
                        $value += 1;
                    }
                    $explanation .= "<li>RSRP (reference signal received power): $lte_rsrp = ";
                    if (!isset($rsrp[$lte_rsrp])) {
                        $explanation .= "-don't-know-";
                    } else {
                        $explanation .= $rsrp[$lte_rsrp] . " dBm";
                    }
                    $explanation .= " </li >";

                    // SINR:
                    $value = -20;
                    $sinr = array('≤ ' . $value);
                    for ($idx = 1; $idx <= 251; ++$idx) {
                        array_push($sinr, $value);
                        $value += 0.2;
                    }
                    $explanation .= "<li>SINR (signal to interference plus noise ratio): $lte_sinr = ";
                    if (!isset($sinr[$lte_sinr])) {
                        $explanation .= "-don't-know-";
                    } else {
                        $explanation .= $sinr[$lte_sinr] . " dB";
                    }
                    $explanation .= " </li >";

                    // RSRQ:
                    $value = -19.5;
                    $rsrq = array('≤ ' . $value);
                    for ($idx = 1; $idx <= 34; ++$idx) {
                        array_push($rsrq, $value);
                        $value += 0.5;
                    }
                    $explanation .= "<li>RSRQ (reference signal received quality): $lte_rsrq = ";
                    if (!isset($rsrq[$lte_rsrq])) {
                        $explanation .= "-don't-know-";
                    } else {
                        $explanation .= $rsrq[$lte_rsrq] . " dB";
                    }
                    $explanation .= " </li >";
                    break;
            }
            $explanation .= "</ul > ";
            break;

        case "ANQUERY":
            $values = explode(',', $args);
            if (count($values) != 5 && count($values) != 6) {
                throw new RuntimeException("Doesn't look anything I know of!");
            }
            // This is early stuff:
            // LTE:   ^ANQUERY:0,99,10,4,108,7
            // WCDMA: ^ANQUERY:80,6,17,4,0x<cellid>?
            // GSM:   ^ANQUERY:0,0,21,0,0xFFFFFFFF
            $explanation = "Network query:<ul>";
            for ($idx = 0; isset($values[$idx]); ++$idx) {
                switch ($idx) {
                    case 0:
                        $explanation .= "<li>? = " . $values[$idx];
                        $explanation .= " </li >";
                        break;
                    case 1:
                        $explanation .= "<li>bit error rate: " . $values[$idx] . " = ";
                        if ($values[1] == 99) {
                            $explanation .= "(not supported currently and only 99 can be displayed)";
                        } else {
                            $explanation .= "-don't-know-";
                        }
                        $explanation .= " </li >";
                        break;
                    case 2:
                        $value = -113;
                        $rssi = array('≤ ' . $value);
                        for ($idx2 = 1; $idx2 <= 30; ++$idx2) {
                            $value += 2;
                            array_push($rssi, $value);
                        }
                        $value += 2;
                        array_push($rssi, '≥ ' . $value);
                        $explanation .= "<li>RSSI: " . $values[$idx] . " = ";
                        if (!isset($rssi[$values[$idx]])) {
                            $explanation .= "-don't-know-";
                        } else {
                            $explanation .= $rssi[$values[$idx]] . " dBm";
                        }
                        $explanation .= " </li >";
                        break;
                    case 3:
                        $explanation .= "<li>? = " . $values[$idx];
                        $explanation .= " </li >";
                        break;
                    case 4:
                        $explanation .= "<li>RSRP (reference signal received power): = -" . $values[$idx] . " dBm";
                        $explanation .= " </li >";
                        break;
                    case 5:
                        $explanation .= "<li>? = " . $values[$idx];
                        $explanation .= " </li >";
                        break;
                }
            }
            $explanation .= "</ul > ";
            break;

        case "CSNR":
            $values = explode(',', $args);
            if (count($values) != 2) {
                throw new RuntimeException("Doesn't look anything I know of!");
            }
            $explanation = "Signal to Noise Ratio?:<ul>";
            for ($idx = 0; isset($values[$idx]); ++$idx) {
                switch ($idx) {
                    case 0:
                    case 1:
                        $explanation .= "<li>? = " . $values[$idx];
                        $explanation .= " </li >";
                        break;
                }
            }
            $explanation .= "</ul > ";
            break;

        case "LTERSRP":
            $values = explode(',', $args);
            if (count($values) != 2) {
                throw new RuntimeException("Doesn't look anything I know of!");
            }
            $explanation = "RSRP (reference signal received power):<ul>";
            for ($idx = 0; isset($values[$idx]); ++$idx) {
                switch ($idx) {
                    case 0:
                    case 1:
                        $explanation .= "<li>? = " . $values[$idx];
                        $explanation .= " </li >";
                        break;
                }
            }
            $explanation .= "</ul > ";
            break;

        case "SYSCFG":
            $values = explode(',', $args);
            if (count($values) != 5) {
                throw new RuntimeException("Doesn't look anything I know of!");
            }
            $explanation = "(NOTE: Don't use this obsoleted setting. Use SYSCFGEX for accurate results.)<br>\nConfigure System:<ul>";
            for ($idx = 0; isset($values[$idx]); ++$idx) {
                switch ($idx) {
                    case 0:
                        $acqorder = array(
                            2 => 'Automatic',
                            13 => 'GSM',
                            14 => 'WCDMA',
                            16 => 'LTE'
                        );
                        $explanation .= "<li>Mode:<br>\n" . $values[$idx] . " = ";
                        if (!isset($acqorder[$values[$idx]])) {
                            $explanation .= "-don't-know-";
                        } else {
                            $explanation .= $acqorder[$values[$idx]];
                        }
                        $explanation .= " </li >";
                        break;
                    case 1:
                        $acqorder = array(
                            'Automatic',
                            'GSM',
                            'WCDMA',
                            'LTE'
                        );
                        $explanation .= "<li>Network aquire order (4G/3G/2G): ";
                        if (!isset($acqorder[$values[$idx]])) {
                            $explanation .= "-don't-know-";
                        } else {
                            $explanation .= $acqorder[$values[$idx]];
                        }
                        $explanation .= " </li >";
                        break;
                    case 2:
                        $bands = array(
                            '00000080' => array('CM_BAND_PREF_GSM_DCS_1800', 'GSM DCS systems', '1800 MHz'),
                            '00000100' => array('CM_BAND_PREF_GSM_EGSM_900', 'Extended GSM 900', '900 MHz'),
                            '00080000' => array('CM_BAND_PREF_GSM_850', 'GSM 850', '850 MHz'),
                            '00200000' => array('CM_BAND_PREF_GSM_PCS_1900', 'GSM PCS', '1900 MHz'),
                            '00400000' => array('CM_BAND_PREF_WCDMA_I_IMT_2000', 'WCDMA IMT 2100', '2100 MHz'),
                            '00800000' => array('CM_BAND_PREF_WCDMA_II_PCS_1900', 'WCDMA_II_PCS_1900', '1900 MHz'),
                            '02000000' => array('CM_BAND_PREF_WCDMA_IX_1700', 'AWS', '1700 MHz'),
                            '04000000' => array('CM_BAND_PREF_WCDMA_V_850', 'WCDMA_V_850', '850 MHz'),
                            '3FFFFFFF' => array('CM_BAND_PREF_ANY', 'any band', '850,900,1700,1800,1900,2100 MHz'),
                            '40000000' => array('CM_BAND_PREF_NO_CHANGE', 'band not changed', null),
                            '0002000000000000' => array('CM_BAND_PREF_WCDMA_VIII_900', 'WCDMA_VIII_900', '900 MHz'),
                            '00680380' => array(null, 'Automatic', '850,900,1700,1800,1900,2100 MHz')
                        );
                        $explanation .= "<li>2G/3G Frequency band preference:<br>\n" . $values[$idx] . " = ";
                        if (!isset($bands[$values[$idx]])) {
                            $explanation .= "-don't-know-";
                        } else {
                            $explanation .= $bands[$values[$idx]][0] . "<br>\n" .
                                $bands[$values[$idx]][1] . ": " . $bands[$values[$idx]][2];
                        }
                        $explanation .= " </li >";
                        break;
                    case 3:
                        $roam = array(
                            'Not supported',
                            'Supported',
                            'No change'
                        );
                        $explanation .= "<li>Roaming: " . $values[$idx] . " = ";
                        if (!isset($roam[$values[$idx]])) {
                            $explanation .= "-don't-know-";
                        } else {
                            $explanation .= $roam[$values[$idx]];
                        }
                        $explanation .= " </li >";
                        break;
                    case 4:
                        $srvdomain = array(
                            'Circuit Switched (CS) only',
                            'Packet Switched (PS) only',
                            'Circuit or Packet Switched',
                            'ANY',
                            'No change'
                        );
                        $explanation .= "<li>Service domain: " . $values[$idx] . " = ";
                        if (!isset($srvdomain[$values[$idx]])) {
                            $explanation .= "-don't-know-";
                        } else {
                            $explanation .= $srvdomain[$values[$idx]];
                        }
                        $explanation .= " </li >";
                        break;
                }
            }
            $explanation .= "</ul > ";
            break;

        case "SYSCFGEX":
            $values = explode(',', $args);
            if (count($values) != 5) {
                throw new RuntimeException("Doesn't look anything I know of!");
            }
            $explanation = "Configure Extended System:<ul>";
            for ($idx = 0; isset($values[$idx]); ++$idx) {
                switch ($idx) {
                    case 0:
                        $acqorder = array(
                            '"00"' => 'Automatic',
                            '"01"' => 'GSM',
                            '"02"' => 'WCDMA',
                            '"03"' => 'LTE',
                            '"99"' => 'Not change'
                        );
                        $explanation .= "<li>Network aquire order (4G/3G/2G): ";
                        if (!isset($acqorder[$values[$idx]])) {
                            $explanation .= "-don't-know-";
                        } else {
                            $explanation .= $acqorder[$values[$idx]];
                        }
                        $explanation .= " </li >";
                        break;
                    case 1:
                        $bands = array(
                            '00000080' => array('CM_BAND_PREF_GSM_DCS_1800', 'GSM DCS systems', '1800 MHz'),
                            '00000100' => array('CM_BAND_PREF_GSM_EGSM_900', 'Extended GSM 900', '900 MHz'),
                            '00080000' => array('CM_BAND_PREF_GSM_850', 'GSM 850', '850 MHz'),
                            '00200000' => array('CM_BAND_PREF_GSM_PCS_1900', 'GSM PCS', '1900 MHz'),
                            '00400000' => array('CM_BAND_PREF_WCDMA_I_IMT_2000', 'WCDMA IMT 2100', '2100 MHz'),
                            '00800000' => array('CM_BAND_PREF_WCDMA_II_PCS_1900', 'WCDMA_II_PCS_1900', '1900 MHz'),
                            '02000000' => array('CM_BAND_PREF_WCDMA_IX_1700', 'AWS', '1700 MHz'),
                            '04000000' => array('CM_BAND_PREF_WCDMA_V_850', 'WCDMA_V_850', '850 MHz'),
                            '3FFFFFFF' => array('CM_BAND_PREF_ANY', 'any band', '850,900,1700,1800,1900,2100 MHz'),
                            '40000000' => array('CM_BAND_PREF_NO_CHANGE', 'band not changed', null),
                            '0002000000000000' => array('CM_BAND_PREF_WCDMA_VIII_900', 'WCDMA_VIII_900', '900 MHz'),
                            '00680380' => array(null, 'Automatic', '850,900,1700,1800,1900,2100 MHz')
                        );
                        $explanation .= "<li>2G/3G Frequency band preference:<br>\n" . $values[$idx] . " = ";
                        if (!isset($bands[$values[$idx]])) {
                            $explanation .= "-don't-know-";
                        } else {
                            $explanation .= $bands[$values[$idx]][0] . "<br>\n" .
                                $bands[$values[$idx]][1] . ": " . $bands[$values[$idx]][2];
                        }
                        $explanation .= " </li >";
                        break;
                    case 2:
                        $roam = array(
                            'Not supported',
                            'Supported',
                            'No change'
                        );
                        $explanation .= "<li>Roaming: " . $values[$idx] . " = ";
                        if (!isset($roam[$values[$idx]])) {
                            $explanation .= "-don't-know-";
                        } else {
                            $explanation .= $roam[$values[$idx]];
                        }
                        $explanation .= " </li >";
                        break;
                    case 3:
                        $srvdomain = array(
                            'Circuit Switched (CS) only',
                            'Packet Switched (PS) only',
                            'Circuit or Packet Switched',
                            'ANY',
                            'No change'
                        );
                        $explanation .= "<li>Service domain: " . $values[$idx] . " = ";
                        if (!isset($srvdomain[$values[$idx]])) {
                            $explanation .= "-don't-know-";
                        } else {
                            $explanation .= $srvdomain[$values[$idx]];
                        }
                        $explanation .= " </li >";
                        break;
                    case 4:
                        // http://www.awt-global.com/resources/lte-e-utran-bands/
                        $lte_bands = array(
                            0x1 => array('CM_BAND_PREF_LTE_EUTRAN_BAND1', 'LTE BC1', '2100 MHz'),
                            0x2 => array('CM_BAND_PREF_LTE_EUTRAN_BAND2', 'LTE BC2', '1900 MHz'),
                            0x4 => array('CM_BAND_PREF_LTE_EUTRAN_BAND3', 'LTE BC3', '1800 MHz'),
                            0x8 => array('CM_BAND_PREF_LTE_EUTRAN_BAND4', 'LTE BC4', '2100 MHz'),
                            0x10 => array('CM_BAND_PREF_LTE_EUTRAN_BAND5', 'LTE BC5', '850 MHz'),
                            0x40 => array('CM_BAND_PREF_LTE_EUTRAN_BAND7', 'LTE BC7', '2600 MHz'),
                            0x80 => array('CM_BAND_PREF_LTE_EUTRAN_BAND8', 'LTE BC8', '900 MHz'),
                            0x1000 => array('CM_BAND_PREF_LTE_EUTRAN_BAND13', 'LTE BC13', '700 MHz'),
                            0x10000 => array('CM_BAND_PREF_LTE_EUTRAN_BAND17', 'LTE BC17', '700 MHz'),
                            0x80000 => array('CM_BAND_PREF_LTE_EUTRAN_BAND20', 'LTE BC20', '800 MHz'),
                            0x40000000 => array('CM_BAND_', 'no change', '? MHz'),
                        );
                        $explanation .= "<li>4G Frequency bands used: 0x" . $values[$idx] . " = <br>\n";
                        $band_bits_total = hexdec($values[$idx]);
                        foreach ($lte_bands as $band_bits => $band_info) {
                            if ($band_bits_total & $band_bits) {
                                $explanation .= '0x' . dechex($band_bits) . ": " .
                                    $band_info[1] . ": " . $band_info[2] . "<br>\n";
                            }
                        }
                        $explanation .= " </li >";
                        break;
                }
            }
            $explanation .= "</ul > ";
            break;

        case "CERSSI":
            $values = explode(',', $args);
            if (count($values) != 11) {
                throw new RuntimeException("Doesn't look anything I know of!");
            }
            $explanation = "RSSI:<ul>";
            $explanation .= "</ul > ";
            break;

        default:
            throw new RuntimeException("Don't know that command!");
    }

    return $explanation;
}

/**
 * RSSI explanator helper
 * @param $RSSI_in
 * @return string RSSI explanation
 */
function RSSI_calculator($RSSI_in)
{
    $value = -120;
    $rssi = array('≤ ' . $value);
    for ($idx = 1; $idx <= 96; ++$idx) {
        array_push($rssi, $value);
        $value += 2;
    }
    if (isset($rssi[$RSSI_in])) {
        return $rssi[$RSSI_in] . " dBm";
    }

    return "-don't-know-";
}

/*
 * Handle incoming AJAX-requests
 */
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    if (!isset($_GET['op'])) {
        throw new RuntimeException("Need operation!");
    }
    if ($_GET['op'] != 'explain') {
        throw new RuntimeException("Unknown operation!");
    }
    if (!isset($_POST['json'])) {
        throw new RuntimeException("Need something to process!");
    }
    $data = json_decode($_POST['json']);
    if (!isset($data->output)) {
        throw new RuntimeException("Need something to explain!");
    }

    if (!preg_match("/^(.)(.+):\s*(.*)$/", $data->output, $matches)) {
        throw new RuntimeException("Doesn't look like Huawei output!");
    }

    $first_char = $matches[1];
    $command = $matches[2];
    $args = $matches[3];

    $ret = null;
    try {
        $explanation = QueryResolver($first_char, $command, $args);
    } catch (RuntimeException $ex) {
        $ret = array('error' => $ex->getMessage());
    }
    if ($ret != null) {
        $data_out = $ret;
    } else {
        $data_out = array('explanation' => $explanation);
    }
    if (headers_sent()) {
        throw new RuntimeException("Internal error: Cannot send JSON!");
    }
    header('Content - Type: application / json');
    print json_encode($data_out);
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Huawei AT-command explanator</title>
    <script type="text/javascript" src="js/dojo.js"></script>
    <script type="text/javascript" src="js/at_cmd.js"></script>
</head>

<body>

<h1>Huawei AT-command explanator</h1>

<form id="the_form">
    <table>
        <tr>
            <td>AT-command<br>to run:</td>
            <td><select>
                    <option value="">-select-command-</option>
                    <option>^SETPORT?</option>
                    <option>+COPS?</option>
                    <option>^LOCINFO?</option>
                    <option>+CEREG?</option>
                    <option>+CSQ</option>
                    <option>^HCSQ?</option>
                    <option>^ANQUERY?</option>
                    <option>^CSNR?</option>
                    <option>^SYSCFG?</option>
                    <option>^SYSCFGEX?</option>
                    <option>^CERSSI?</option>
                    <option>^LTERSRP?</option>
                </select>
            </td>
        </tr>
        <tr>
            <td>command<br>output:</td>
            <td><input id="output_text" type="text" value="" size="60"><br>
                <input type="button" id="explain_btn" value="Explain output" style="margin-top: 10px;">
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <hr/>
            </td>
        </tr>
        <tr style="vertical-align: top; height: 400px;">
            <td>Explanation:</td>
            <td style="background-color:#eeeeee;">
                <div id="explanation"></div>
            </td>
        </tr>
    </table>
</form>

<footer id="bottom_info">
    Source code for this application is available at <a
        href="http://blog.hqcodeshop.fi/">http://blog.hqcodeshop.fi/</a>.
</footer>
</body>
</html>