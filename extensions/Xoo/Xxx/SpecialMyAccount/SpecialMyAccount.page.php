<?php
 
/**
* Class definition for the SpecialMyAccount special page
*/
 
class SpecialMyAccount extends SpecialPage {
	/**
	* Constructor
	*/
	function SpecialMyAccount() {
		SpecialPage::SpecialPage( 'MyAccount', 'specialmyaccount' );
	}
 
	/**
	* Main execution function
	*/
	function execute( $par ) {
		global $wgRequest, $wgOut, $wgUser;
        
        if (!$wgUser->isLoggedIn()) return false;
        
        $this->userName = $wgUser->getName();

        $this->email = $wgUser->getEmail();
        $this->realName = $wgUser->getRealName();
        $this->phoneNumber = $wgUser->getOption('phoneNumber');
       
        
        if ($wgRequest->wasPosted())
        {
            $this->email=$wgRequest->getVal('email',$this->email);
            $wgUser->setEmail($this->email);
            
            $this->realName = $wgRequest->getVal('realname',$this->realName);
            $wgUser->setRealName($this->realName);
            
            #$this->phoneNumber = $wgRequest->getVal('phonenumber',$this->phoneNumber);
            #$wgUser->setOption('phoneNumber',$this->phoneNumber);

            $password = $wgRequest->getVal('password','');
            $repeat = $wgRequest->getVal('repeatpassword','');
            if ($password and $password==$repeat)
            {
              if($wgUser->isValidPassword($password))
              {
                $wgUser->setPassword($password);
                $footer.=wfMsg("specialmyaccount-passwordchanged");
              }
              else
              {
                $footer.=wfMsg("specialmyaccount-passwordfailed");
              }
            }
            $wgUser->setCookies();
            $wgUser->saveSettings();
            
                       
        }
        $wgOut->addHTML($this->makeAccountForm($footer));
   	}
 
	/**
	* Produce a form to allow for entering a user and confirming their email address
	*/
	
	function makeField($name,$value,$type="text")
	{
		$field = wfOpenElement( 'div', array( 'class' => 'field','id'=>"field-$name" ) );
		if ($type!='hidden') $field .= wfOpenElement( 'label', array( 'class' => "label $type-label",'for' => $name )) . ($type=='submit' ? '&nbsp;' : wfMsg( "specialmyaccount-$name" )) . wfCloseElement('label');
		$field .= wfElement( 'input', array( 'class' => "control input $type",'type' => $type, 'name' => $name, 'id' => $name, 'value' => $value ) ) . ' ';
		$field .= wfCloseElement( 'div');
		return $field;
	}
	function makeAccountForm($footer) {
		$thisTitle = Title::makeTitle( NS_SPECIAL, $this->getName() );
		$form = wfOpenElement( 'form', array( 'id'=>'account-form', 'class'=>'form account-form', 'method' => 'post', 'action' => $thisTitle->getLocalUrl() ) );
		$form .= "<fieldset><legend>" . wfMsg('specialmyaccount-username') . ': ' . $this->userName ."</legend>";
		$form .= "<div class=\"myaccount-message\">$footer</div>";
		$form .= $this->makeField('realname',$this->realName);
		$form .= $this->makeField('email',$this->email);
		#$form .= $this->makeField('phonenumber',$this->phoneNumber);
		$form .= $this->makeField('password','','password');
		$form .= $this->makeField('repeatpassword','','password');
		if (isset($_REQUEST['customskin'])) $form .= $this->makeField('customskin',$_REQUEST['customskin'],'hidden');
		$form .= $this->makeField('savechanges',wfMsg('specialmyaccount-savechanges'),'submit');
		$form .= wfCloseElement( 'fieldset' );
		$form .= wfCloseElement( 'form' );
		return $form;
	}
 
	/**
	* Produce a form to allow setting a user's email address
	*/
 
	function makeSetForm() {
		$thisTitle = Title::makeTitle( NS_SPECIAL, $this->getName() );
		$form = wfOpenElement( 'form', array( 'method' => 'post', 'action' => $thisTitle->getLocalUrl() ) );
		$form .= wfElement( 'label', array( 'for' => 'email' ), wfMsg( 'specialmyaccount-email' ) ) . ' ';
		$form .= wfElement( 'input', array( 'type' => 'text', 'name' => 'email', 'id' => 'email', 'value' => $this->email ) ) . ' ';
		$form .= wfElement( 'input', array( 'type' => 'hidden', 'name' => 'username', 'id' => 'username', 'value' => $this->target ) ) . ' ';
		$form .= wfElement( 'input', array( 'type' => 'submit', 'name' => 'setemail', 'value' => wfMsg( 'specialmyaccount-set' ) ) );
		$form .= wfCloseElement( 'form' );
		return $form;
	}
	
	function shook_UserCreateForm(&$template)
	{
	    $template->set('specialmyaccount-phonenumber',0);
	}
}

