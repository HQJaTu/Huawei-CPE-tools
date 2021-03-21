<?php
/**
 * Created by PhpStorm.
 * User: Jari
 * Original date: 16.8.2014
 * Date: 21.3.2021
 */

require('./vendor/autoload.php');

use PhpAes\Aes;

/**
 * Hard-coded "product info" from B593 firmware.
 * I guess, this could change from device to device.
 */
define('PRODUCT_INFO', "12345678");
define('KEY_LEN', 8);


/**
 * Extract password
 *
 * @param string
 * @return string
 */
function ExtractAES128PasswordFromEncryptedData($encrypted_data)
{
    if (strlen($encrypted_data) < 10) {
        throw new OutOfRangeException("This doesn't look like an encrypted B593 password!");
    }
    if ($encrypted_data[KEY_LEN] != ':') {
        throw new OutOfRangeException("This doesn't look like an encrypted B593 password!");
    }

    $key_in = substr($encrypted_data, 0, KEY_LEN);

    return $key_in;
}

/**
 * Encrypt a password
 *
 * @param $plaintext
 * @param $key_in
 * @return string Encrypted data
 */
function EncryptPassword($plaintext, $key_in)
{
    if (strlen($key_in) != KEY_LEN) {
        throw new OutOfRangeException("Encryption key must be exactly 8 characters!");
    }

    $key = PRODUCT_INFO . $key_in;
    /*
     * using deprecated mcrypt-extension

    $ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key,
        $plaintext, MCRYPT_MODE_ECB);
    */

    $aes = new Aes($key, 'ECB');
    $ciphertext = $aes->encrypt($plaintext);

    return $key_in . ':' . $ciphertext;
}

/**
 * Decrypt a password
 *
 * @param $encrypted_data Base64-decoded data as stored in B593
 * @return string
 */
function DecryptAES128Password($encrypted_data)
{
    $key_in = ExtractAES128PasswordFromEncryptedData($encrypted_data);
    $ciphertext_no_key = substr($encrypted_data, KEY_LEN + 1);
    $key = PRODUCT_INFO . $key_in;
    /*
     * using deprecated mcrypt-extension
    $plaintext_orig = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key,
        $ciphertext_no_key, MCRYPT_MODE_ECB);
    */

    $aes = new Aes($key, 'ECB');
    $plaintext = $aes->decrypt($ciphertext_no_key);

    return $plaintext;
}

/*
 * Handle incoming AJAX-requests
 */
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    if (!isset($_GET['op'])) {
        throw new RuntimeException("Need operation!");
    }

    $op = substr($_GET['op'], 0, 16);
    switch ($op) {
        case "decrypt":
            if (!isset($_POST['json'])) {
                throw new RuntimeException("Need JSON-data!");
            }
            $data = json_decode($_POST['json']);
            if (!isset($data->crypto)) {
                throw new RuntimeException("Need crypto to use!");
            }
            if (!isset($data->target)) {
                throw new RuntimeException("Need target to decrypt for!");
            }
            if (!isset($data->encrypted)) {
                throw new RuntimeException("Need encrypted value to decrypt!");
            }
            $encrypted_data = base64_decode($data->encrypted);
            if (!$encrypted_data) {
                throw new RuntimeException("Base64 decode failed!");
            }

            switch ($data->crypto) {
                case "des":
                    break;
                case "aes":
                    switch ($data->target) {
                        case "ssh":
                        case "web":
                            break;

                        case "ftp":
                        case "wifi":
                            try {
                                $key = ExtractAES128PasswordFromEncryptedData($encrypted_data);
                                $password = DecryptAES128Password($encrypted_data);
                            } catch (Exception $ex) {
                                throw new RuntimeException("Decrypting failed!", 500, $ex);
                            }
                            break;
                        default:
                            throw new RuntimeException("Unknown target!");
                    }
                    break;
                default:
                    throw new RuntimeException("Unknown crypto!");
            }

            // Post-process:
            // Strip out chr(0) from the end of password
            for ($idx = 0; $idx < strlen($password); ++$idx) {
                if (ord($password[$idx]) == 0) {
                    $password = substr($password, 0, $idx);
                    break;
                }
            }
            if (strlen($password) == 0) {
                // Something went horribly wrong!
                // After stripping, there is nothing left out of the plaintext.
                $password = null;
            }

            // Output
            $data_out = array("key" => $key,
                "password" => $password);
            if (headers_sent()) {
                throw new RuntimeException("Internal error: Cannot send JSON!");
            }
            header('Content-Type: application/json');
            print json_encode($data_out);
            exit();

        case "encrypt":
            if (!isset($_POST['json'])) {
                throw new RuntimeException("Need JSON-data!");
            }
            $data = json_decode($_POST['json']);
            if (!isset($data->crypto)) {
                throw new RuntimeException("Need crypto to use!");
            }
            if (!isset($data->target)) {
                throw new RuntimeException("Need target to encrypt for!");
            }
            if (!isset($data->password) || !isset($data->key)) {
                throw new RuntimeException("Need key and password to encrypt!");
            }
            if (strlen($data->key) != 8) {
                throw new RuntimeException("Key must be 8 characters!");
            }

            try {
                $encrypted_data = EncryptPassword($data->password, $data->key);
            } catch (Exception $ex) {
                throw new RuntimeException("Encrypting failed!", 500, $ex);
            }

            // Output
            $base64_encoded = base64_encode($encrypted_data);
            $data_out = array("ciphertext" => $base64_encoded);
            if (headers_sent()) {
                throw new RuntimeException("Internal error: Cannot send JSON!");
            }
            header('Content-Type: application/json');
            print json_encode($data_out);
            exit();

        default:
            throw new BadFunctionCallException("Don't know how to do '$op'!");
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Huawei B593 u-12 Password Recovery</title>
    <script type="text/javascript" src="js/dojo.js"></script>
    <script type="text/javascript" src="js/ajax.js"></script>
</head>

<body>

<h1>Huawei B593 u-12 Password Recovery</h1>

<form id="the_form">
    <table>
        <tr>
            <td>Encryption:</td>
            <td><input id="encryption_des" type="radio" name="encryption" value="des" disabled><strike>3-DES, pre
                    SP100</strike><br>
                <input id="encryption_aes" type="radio" name="encryption" value="aes" checked>AES-128 EBC, SP100+
            </td>
        </tr>
        <tr>
            <td>Target:</td>
            <td><input id="target_gui" type="radio" name="target" value="gui" disabled><strike>Web GUI</strike><br>
                <input id="target_ssh" type="radio" name="target" value="ssh" disabled><strike>SSH-user</strike><br>
                <input id="target_ftp" type="radio" name="target" value="ftp">FTP-user<br>
                <input id="target_wifi" type="radio" name="target" value="wifi">Wi-Fi key
            </td>
        </tr>
        <tr>
            <td>Base64 encoded:</td>
            <td><input id="encrypted_password" type="text" value="" size="60"><br>
            </td>
        </tr>
        <tr>
            <td>Key:</td>
            <td><input id="key" type="text" value="" size="10" maxlength="8"><br>
            </td>
        </tr>
        <tr>
            <td>Plain-text:</td>
            <td><input id="decrypted_password" type="text" value="" size="20" maxlength="15"> (6-15 chars)<br>
            </td>
        </tr>
        <tr>
            <td colspan="2"><input type="button" id="submit_btn" value="" style="display:none;"></td>
        </tr>
    </table>
</form>

<footer id="bottom_info">
    For source code, see <a
            href="https://github.com/HQJaTu/Huawei-CPE-tools">https://github.com/HQJaTu/Huawei-CPE-tools</a>.
</footer>
</body>
</html>