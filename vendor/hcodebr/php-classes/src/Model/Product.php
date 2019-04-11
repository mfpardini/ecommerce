<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class Product extends Model
{
    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_products ORDER BY desproduct");
    }

    public static function checkList($list)
    {
        foreach ($list as &$row) {

            $p = new Product();
            $p->setData($row);
            $row = $p->getValues();
        }

        return $list;
    }

    public function save()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_products_save(:idproduct, :desproduct, :vlprice, :vlwidth, :vlheight, :vllength, :vlweight, :desurl)", array(
            ":idproduct"=>$this->getidproduct(),
            ":desproduct"=>$this->getdesproduct(),
            ":vlprice"=>$this->getvlprice(),
            ":vlwidth"=>$this->getvlwidth(),
            ":vlheight"=>$this->getvlheight(),
            ":vllength"=>$this->getvllength(),
            ":vlweight"=>$this->getvlweight(),
            ":desurl"=>$this->getdesurl()
        ));

        $this->setData($results[0]);
    }

    public function get($idproduct)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_products WHERE idproduct = :idproduct", [
            'idproduct'=>$idproduct
        ]);

        $this->setData($results[0]);
    }

    public function delete()
    {
        $sql = new Sql();

        $sql->query("DELETE FROM tb_products WHERE idproduct = :idproduct", [
            'idproduct'=>$this->getidproduct()
        ]);
    }

    public function checkPhoto()
    {
        $dest = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 
            "res" . DIRECTORY_SEPARATOR . 
            "site" . DIRECTORY_SEPARATOR . 
            "img" . DIRECTORY_SEPARATOR . 
            "products" . DIRECTORY_SEPARATOR . 
            $this->getidproduct() . ".jpg";
            
        if (file_exists($dest))
        {
            $url = "/res/site/img/products/" . $this->getidproduct() . ".jpg";
        }
        else
        {
            $url = "/res/site/img/product.jpg";
        }

        $this->setdesphoto($url);
    }

    public function getValues()
    {
        $this->checkPhoto();

        $values = parent::getValues();

        return $values;
    }

    public function setPhoto($file)
    {

        $extension = explode('.', $file['name']);
        $extension = end($extension);

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($file['tmp_name']);
            break;

            case 'gif':
                $image = imagecreatefromgif($file['tmp_name']);
            break;
            case 'png':
                $image = imagecreatefrompng($file['tmp_name']);
            break;
        }

        $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
        imagealphablending($bg, TRUE);
        imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagedestroy($image);
        $quality = 70; // 0 = worst / smaller file, 100 = better / bigger file 

        $filePath = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 
                    "res" . DIRECTORY_SEPARATOR . 
                    "site" . DIRECTORY_SEPARATOR . 
                    "img" . DIRECTORY_SEPARATOR . 
                    "products" . DIRECTORY_SEPARATOR . 
                    $this->getidproduct() . ".jpg";

        imagejpeg($bg, $filePath, $quality);
        imagedestroy($bg);

        $this->checkPhoto();
    }

    public function getFromURL($desurl)
    {
        $sql = new Sql();
        
        $rows = $sql->select("SELECT * FROM tb_products WHERE desurl = :desurl LIMIT 1", [
            ':desurl'=>$desurl
        ]);

        $this->setData($rows[0]);

    }

    public function getCategories()
    {
        $sql = new Sql();
        
        return $sql->select("SELECT * FROM tb_categories a
            INNER JOIN tb_categoriesproducts b ON a.idcategory = b.idcategory
            WHERE b.idproduct = :idproduct", [
            ':idproduct'=>$this->getidproduct()
        ]);

    }


}

?>