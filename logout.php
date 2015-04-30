<?php
/**
 * Destroys the session to logout the user and redirect to Homepage
 * @author Avin E.M
 */
session_start();
session_destroy();
header("Location: ./");    
?>