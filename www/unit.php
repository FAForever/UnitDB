<?php
	/*
		
		This is a legacy file, only here to ensure seamless transition with the old database.
		Basically just redirects to index.php
	*/
	
	header('Location: index.php?id='.$_GET['bp'].'');
	
?>