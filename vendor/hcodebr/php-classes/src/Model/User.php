<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class User extends Model
{
    const SESSION = "User";
    const KEY = "d64ca13d94fe69192a8c136595b161798ccbb9e6a3d5eb2029164c4311e20446";
    
    public static function getFromSession()
    {
        $user = new User();

        if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0)
        {
            $user->setData($_SESSION[User::SESSION]);
        }

        return $user;
    }

    public static function login($login, $passsword)
    {

        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
            ":LOGIN"=>$login
        ));

        if(count($results) === 0)
        {
            throw new \Exception("Usuário inexistente ou senha inválida.");
        }

        $data = $results[0];

        if (password_verify($passsword, $data["despassword"]) === true)
        {

            $user = new User();

            $user->setData($data);

            $_SESSION[User::SESSION] = $user->getValues();
            
            return $user;

        }
        else 
        {
            throw new \Exception("Usuário inexistente ou senha inválida."); 
        }
    }

    public static function checkLogin($inadmin = true)
    {

        if (
            !isset($_SESSION[User::SESSION])
            ||
            !$_SESSION[User::SESSION]
            ||
            !(int)$_SESSION[User::SESSION]["iduser"] > 0
        ) {
            //Não está logado
            return false;
        }
        else // está logado:
        {
            if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true)
            {
                return true;
            }
            else if ($inadmin === false)
            {
                return true;
            }
            else
            {
                return false; 
            }
        }

    }

    public static function verifyLogin($inadmin = true)
    {

        if (User::checkLogin)
        {
            header("Location: /admin/login");
            exit;
        }
    }

    public static function logout()
    {

        $_SESSION[User::SESSION] = NULL;
    }

    public static function listAll()
    {

        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
    }

    public function save()
    {

        $sql = new Sql();

        $results = $sql->select(
        "CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",
        array(
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>$this->getdespassword(),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));

        $this->setData($results[0]);
    }

    public function get($iduser)
    {

        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
            ":iduser"=>$iduser
        ));

        $this->setData($results[0]);
    }

    public function update()
    {

        $sql = new Sql();

        $results = $sql->select(
        "CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",
        array(
            ":iduser"=>$this->getiduser(),
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>$this->getdespassword(),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));

        $this->setData($results[0]);
    }

    public function delete()
    {

        $sql = new Sql();

        $sql->query("CALL sp_users_delete(:iduser)", array(
            ":iduser"=>$this->getiduser()
        ));

    }

    private function encrypt_decrypt($action, $string)
    {
        $key = User::KEY;
        $output = false;
        $cipher = 'AES-256-CBC';
                
        if( $action === 'encrypt')
        {
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
            $output = base64_encode(openssl_encrypt($string, $cipher, $key, 0, $iv));
            return $output."::".bin2hex($iv);

        }
        else if ( $action === 'decrypt')
        {
            $string = explode("::", $string);
            $toDecode = base64_decode($string[0]);
            $iv = hex2bin($string[1]);
            $output = openssl_decrypt($toDecode, $cipher, $key, 0, $iv);
            return $output;
        }
    }

    public static function getForgot($email)
    {

        $sql = new Sql();

        $results = $sql->select("
            SELECT * FROM tb_persons a
            INNER JOIN tb_users b
            USING(idperson)
            WHERE a.desemail = :email
        ", array(
            ":email"=>$email
        ));

        if (count($results[0]) === 0)
        {
            throw new \Exception("Não foi possível recuperar a senha");
        }

        else
        {
            $data = $results[0];

            $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
                ":iduser"=>$data['iduser'],
                ":desip"=>$_SERVER["REMOTE_ADDR"]
            ));

            if (count($results2[0]) === 0 )
            {
                throw new \Exception("Não foi possível recuperar a senha");
            }
            else
            {
                $dataRecovery = $results2[0];

                $code = self::encrypt_decrypt('encrypt', $dataRecovery["idrecovery"]);
                
                $link = "http://www.pardinicommerce.com.br/admin/forgot/reset?code=$code";

                $mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir senha da Pardini Store", "forgot", array(
                        "name"=>$data["desperson"],
                        "link"=>$link
                ));

                $mailer->send();
                
                return $data;
            }
        }
    }

    public static function validForgotDecrypt($code)
    {
        $idrecovery = self::encrypt_decrypt('decrypt', $code);

        $sql = new Sql();

        $results = $sql->select("
            SELECT *
            FROM tb_userspasswordsrecoveries a
            INNER JOIN tb_users b USING(iduser)
            INNER JOIN tb_persons c USING(idperson)
            WHERE
                a.idrecovery = :idrecovery
                AND
                a.dtrecovery IS NULL
                AND
                DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
                ", array(
                    ":idrecovery"=>$idrecovery
                ));

        if (count($results) === 0)
        {
            throw new \Exception("Não foi possível recuperar a senha");
        }
        else
        {
            return $results[0];
        }
    }

    public static function setForgotUsed($idrecovery)
    {

        $sql = new Sql();

        $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
                ":idrecovery"=>$idrecovery
        ));
    }

    public function setPassword($password)
    {
        $sql = new Sql();

        $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
            ":password"=>$password,
            ":iduser"=>$this->getiduser()
        ));

    }
}

?>