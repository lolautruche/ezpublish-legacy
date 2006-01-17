<?php
//
// Created on: <02-May-2002 16:24:15 bf>
//
// Copyright (C) 1999-2006 eZ systems as. All rights reserved.
//
// This source file is part of the eZ publish (tm) Open Source Content
// Management System.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 as published by the Free
// Software Foundation and appearing in the file LICENSE included in
// the packaging of this file.
//
// Licencees holding a valid "eZ publish professional licence" version 2
// may use this file in accordance with the "eZ publish professional licence"
// version 2 Agreement provided with the Software.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "eZ publish professional licence" version 2 is available at
// http://ez.no/ez_publish/licences/professional/ and in the file
// PROFESSIONAL_LICENCE included in the packaging of this file.
// For pricing of this licence please contact us via e-mail to licence@ez.no.
// Further contact information is available at http://ez.no/company/contact/.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.
//
// Contact licence@ez.no if any conditions of this licencing isn't clear to
// you.
//

include_once( 'lib/ezutils/classes/ezhttptool.php' );
include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );
include_once( 'kernel/common/template.php' );
include_once( 'lib/ezutils/classes/ezini.php' );
include_once( 'kernel/classes/datatypes/ezuser/ezuserloginhandler.php' );

//$Module->setExitStatus( EZ_MODULE_STATUS_SHOW_LOGIN_PAGE );

$Module =& $Params['Module'];

$ini =& eZINI::instance();
$http =& eZHTTPTool::instance();

$userLogin = '';
$userPassword = '';
$userRedirectURI = '';

$loginWarning = false;

$siteAccessAllowed = true;
$siteAccessName = false;

if ( isset( $Params['SiteAccessAllowed'] ) )
    $siteAccessAllowed = $Params['SiteAccessAllowed'];
if ( isset( $Params['SiteAccessName'] ) )
    $siteAccessName = $Params['SiteAccessName'];

if ( $Module->isCurrentAction( 'Login' ) and
     $Module->hasActionParameter( 'UserLogin' ) and
     $Module->hasActionParameter( 'UserPassword' )
     )
{
    $userLogin = $Module->actionParameter( 'UserLogin' );
    $userPassword = $Module->actionParameter( 'UserPassword' );
    $userRedirectURI = $Module->actionParameter( 'UserRedirectURI' );

    if ( trim( $userRedirectURI ) == "" )
    {
        // Only use redirection if RequireUserLogin is disabled
        $requireUserLogin = ( $ini->variable( "SiteAccessSettings", "RequireUserLogin" ) == "true" );
        if ( !$requireUserLogin )
        {
            if ( $http->hasSessionVariable( "LastAccessesURI" ) )
                $userRedirectURI = $http->sessionVariable( "LastAccessesURI" );
        }

        if ( $http->hasSessionVariable( "RedirectAfterLogin" ) )
        {
            $userRedirectURI = $http->sessionVariable( "RedirectAfterLogin" );
        }
    }

    $user = false;
    if ( $userLogin != '' )
    {
        $http->removeSessionVariable( 'RedirectAfterLogin' );

        $ini =& eZINI::instance();
        if ( $ini->hasVariable( 'UserSettings', 'LoginHandler' ) )
        {
            $loginHandlers = $ini->variable( 'UserSettings', 'LoginHandler' );
        }
        else
        {
            $loginHandlers = array( 'standard' );
        }
        $hasAccessToSite = true;
        foreach ( array_keys ( $loginHandlers ) as $key )
        {
            $loginHandler = $loginHandlers[$key];
            $userClass =& eZUserLoginHandler::instance( $loginHandler );
            $user = $userClass->loginUser( $userLogin, $userPassword );
            if ( get_class( $user ) == 'ezuser' )
            {
                $uri =& eZURI::instance( eZSys::requestURI() );
                $access = accessType( $uri,
                                      eZSys::hostname(),
                                      eZSys::serverPort(),
                                      eZSys::indexFile() );
                $siteAccessResult = $user->hasAccessTo( 'user', 'login' );
                $hasAccessToSite = false;
                // A check that the user has rights to access current siteaccess.
                if ( $siteAccessResult[ 'accessWord' ] == 'limited' )
                {
                    include_once( 'lib/ezutils/classes/ezsys.php' );

                    $policyChecked = false;
                    foreach ( array_keys( $siteAccessResult['policies'] ) as $key )
                    {
                        $policy =& $siteAccessResult['policies'][$key];
                        if ( isset( $policy['SiteAccess'] ) )
                        {
                            $policyChecked = true;
                            if ( in_array( eZSys::ezcrc32( $access[ 'name' ] ), $policy['SiteAccess'] ) )
                            {
                                $hasAccessToSite = true;
                                break;
                            }
                        }
                        if ( $hasAccessToSite )
                            break;
                    }
                    if ( !$policyChecked )
                        $hasAccessToSite = true;
                }
                else if ( $siteAccessResult[ 'accessWord' ] == 'yes' )
                {
                    $hasAccessToSite = true;
                }
                // If the user doesn't have the rights.
                if ( !$hasAccessToSite )
                {
                    $user->logoutCurrent();
                    $user = null;
                    $siteAccessName = $access['name'];
                    $siteAccessAllowed = false;
                }
                break;
            }
        }
        if ( ( get_class( $user ) != 'ezuser' ) and $hasAccessToSite )
            $loginWarning = true;
    }
    else
    {
        $loginWarning = true;
    }

    $redirectionURI = $userRedirectURI;
    if ( $redirectionURI == '' )
        $redirectionURI = $ini->variable( 'SiteSettings', 'DefaultPage' );

//     eZDebug::writeDebug( $user, 'user');
    $userID = 0;
    if ( get_class( $user ) == 'ezuser' )
        $userID = $user->id();
    if ( $userID > 0 )
    {
        $http->removeSessionVariable( 'eZUserLoggedInID' );
        $http->setSessionVariable( 'eZUserLoggedInID', $userID );
        return $Module->redirectTo( $redirectionURI );
    }
}
else
{
    $loginPage = $ini->variable( 'SiteSettings', 'LoginPage' );

    if ( $loginPage == 'embedded' ||
         $loginPage == 'custom' )
    {
        $requestedURI =& $GLOBALS['eZRequestedURI'];
        if ( get_class( $requestedURI ) == 'ezuri' )
        {
            $requestedModule = $requestedURI->element( 0, false );
            $requestedView = $requestedURI->element( 1, false );
            if ( $requestedModule != 'user' or
                 $requestedView != 'login' )
                $userRedirectURI = $requestedURI->uriString( true );
        }
    }
    else
    {
        eZUserLoginHandler::forceLogin();
        return $Module->redirectTo( '/' );
    }
}

if( $http->hasPostVariable( "RegisterButton" ) )
{
    $Module->redirectToView( 'register' );
}
$tpl =& templateInit();

$tpl->setVariable( 'login', $userLogin, 'User' );
$tpl->setVariable( 'password', $userPassword, 'User' );
$tpl->setVariable( 'redirect_uri', $userRedirectURI, 'User' );
$tpl->setVariable( 'warning', array( 'bad_login' => $loginWarning ), 'User' );

$tpl->setVariable( 'site_access', array( 'allowed' => $siteAccessAllowed,
                                         'name' => $siteAccessName ) );

$Result = array();
$Result['content'] =& $tpl->fetch( 'design:user/login.tpl' );
$Result['path'] = array( array( 'text' => ezi18n( 'kernel/user', 'User' ),
                                'url' => false ),
                         array( 'text' => ezi18n( 'kernel/user', 'Login' ),
                                'url' => false ) );
if ( $ini->variable( 'SiteSettings', 'LoginPage' ) == 'custom' )
    $Result['pagelayout'] = 'loginpagelayout.tpl';

?>
