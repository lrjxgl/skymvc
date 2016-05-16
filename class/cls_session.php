<?php
class session{

	public $db;
	function __construct()
	{
		session_module_name('user'); 		
	 
		session_set_save_handler( 
            array(&$this, 'open'), 
            array(&$this, 'close'), 
            array(&$this, 'read'), 
            array(&$this, 'write'), 
            array(&$this, 'destroy'), 
            array(&$this, 'gc')                                                             
        ); 
		session_start();
	}
	function open($save_path, $session_name)
	{
		return true;
	}
	
	function close()
	{
		return true;
	}
	
	function read($id)
	{	 
		return sess_read($id); 
		return false;
	}
	
	function write($id, $sess_data)
	{
		sess_write($id,$sess_data);
		return true;
	}
	
	function destroy($id)
	{
		sess_destroy($id);
		return false;
	}
	
	function gc($maxlifetime)
	{
		sess_gc($maxlifetime);
		return true;
	}

	
}

