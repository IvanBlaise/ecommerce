<?php 

namespace Hcode\Model;

use \Hcode\Model;
use \Hcode\DB\Sql;
use \Hcode\Mailer;

define('SECRET_IV', pack('a16', 'senha'));
define('SECRET', pack('a16', 'senha'));

class User extends Model {

	const SESSION = "User";
	
	public static function getFromSession()
	{
		$user = new User();

		if(isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]["iduser"] > 0){

			$user->setData($_SESSION[User::SESSION]);
		}

		return $user;	

	}

	public static function checkLogin($inadmin = true)
	{
		if(
			!isset($_SESSION[User::SESSION])
			|| 
			!$_SESSION[User::SESSION]
			||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0
		){
			//Não está logado
			return false;

		}else
		{
			if($inadmin === true && (bool)$_SESSION[User::SESSION]["inadmin"] === true){

				return true;

			} else if($inadmin === false){

				return true;

			} else{

				return false;

			}


		}



	}

	

	public static function login($login, $password):User
	{

		$db = new Sql();

		$results = $db->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
			":LOGIN"=>$login
		));

		if (count($results) === 0) {
			throw new \Exception("Não foi possível fazer login.");
		}

		$data = $results[0];

		if (password_verify($password, $data["despassword"])) {

			$user = new User();
			$user->setData($data);

			$_SESSION[User::SESSION] = $user->getValues();

			return $user;

		} else {

			throw new \Exception("Não foi possível fazer login.");

		}

	}

	public static function logout()
	{

		$_SESSION[User::SESSION] = NULL;

	}

	public static function verifyLogin($inadmin = true)
	{

		if (User::checkLogin($inadmin)) {
			
			header("Location: /admin/login");
			exit;

		}

	}

	public static function listAll()
	{
		$sql = new Sql();

		return $sql->select("select * from tb_users a inner join tb_persons b USING(idperson)  order by b.desperson");


	}

	public function save()
	{

		$sql = new Sql();

			
		$results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", 
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

		$result =  $sql-> select("select * from tb_users a INNER JOIN tb_persons b USING(idperson) where a.iduser = :iduser", 
			array(
				"iduser"=>$iduser
		));
			
		$this->setData($result[0]);
		

	}


	public function update()
	{
		$sql = new Sql();

			
		$results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", 
			array(
			"iduser"=>$this->getiduser(),
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
			"iduser"=>$this->getiduser()
		));
	}

	public static function getForgot($email)
	{

		$sql = new Sql();

		$results = $sql->select("select * from tb_persons a
						inner join tb_users b using(idperson)
						where a.desemail = :email", 
						array(
							":email"=>$email
						));
		if(count($results) === 0)
		{

			throw new \Exception("Não foi possível recuperar a senha.");
			
		}else
		{
			$data =  $results[0];	
			$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", 
				array(

					":iduser"=>$data["iduser"],
					":desip"=>$_SERVER["REMOTE_ADDR"]
				));
			if(count($results2) === 0)
			{
				throw new \Exception("Não foi possível recuperar a senha.");
				
			}else
			{
				
				$datarecovery = $results2[0];
				

				$code = base64_encode(openssl_encrypt($datarecovery["idrecovery"],'AES-128-CBC', SECRET, 0, SECRET_IV));

				$link ="http://www.ivancommerce.com.br:8080/admin/forgot/reset?code=$code";

				$mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir Senha da Ivan Store", "forgot", 
					array(
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

		$idrecovery = openssl_decrypt(base64_decode($code),'AES-128-CBC', SECRET, 0, SECRET_IV);

		$sql = new Sql();

		$results = $sql->select("
				SELECT * from tb_userspasswordsrecoveries a
				inner join tb_users b using(iduser)
				inner join tb_persons c using(idperson)
				where 	a.idrecovery = :idrecovery 
						and a.dtrecovery is null 
						and DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW()",
				array(
					":idrecovery"=>$idrecovery
				
		));

		if(count($results) === 0)
		{

			throw new \Exception("Não foi possível recuperar a senha");

		}else
		{

			return $results[0];
		}

	}

	public static function setForgotUsed($idrecovery)
	{
		$sql = new Sql();

		$sql->query("update tb_userspasswordsrecoveries set dtrecovery = NOW() where idrecovery = :idrecovery", 
			array(
				":idrecovery"=>$idrecovery
		));
	}

	public function setPassword($password)
	{
		$sql = new Sql();

		$sql->query("update tb_users set despassword = :password where iduser = :iduser", 
			array(
				":password"=>$password,
				":iduser"=>$this->getiduser()
			));


	}

}

 ?>