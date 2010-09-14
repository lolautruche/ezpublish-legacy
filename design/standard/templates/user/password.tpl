{* DO NOT EDIT THIS FILE! Use an override template instead. *}
<form action={concat($module.functions.password.uri,"/",$userID)|ezurl} method="post" name="Password">

<div class="maincontentheader">
<h1>{"Change password for user"|i18n("design/standard/user")} {$userAccount.login}</h1>
</div>

{section show=$message}
{section show=or($oldPasswordNotValid,$newPasswordNotMatch,$newPasswordTooShort)}
    {section show=$oldPasswordNotValid}
        <div class="warning">
            <h2>{'Please retype your old password.'|i18n('design/standard/user')}</h2>
        </div>
    {/section}
    {section show=$newPasswordNotMatch}
        <div class="warning">
            <h2>{"Password did not match. Please retype your new password."|i18n('design/standard/user')}</h2>
        </div>
    {/section}
    {section show=$newPasswordTooShort}
        <div class="warning">
            <h2>{"The new password must be at least %1 characters long. Please retype your new password."|i18n( 'design/standard/user','',array( ezini('UserSettings','MinPasswordLength') ) )}</h2>
        </div>
    {/section}

{section-else}
    <div class="feedback">
        <h2>{'Password successfully updated.'|i18n('design/standard/user')}</h2>
    </div>
{/section}

{/section}

<div class="block">
{section show=$oldPasswordNotValid}*{/section}
<label>{"Old password"|i18n("design/standard/user")}</label><div class="labelbreak"></div>
<input class="halfbox" type="password" name="oldPassword" size="11" value="{$oldPassword|wash}" />
</div>

<div class="block">
<div class="element">
{section show=$newPasswordNotMatch}*{/section}
<label>{"New password"|i18n("design/standard/user")}</label><div class="labelbreak"></div>
<input class="halfbox" type="password" name="newPassword" size="11" value="{$newPassword|wash}" />
</div>
<div class="element">
{section show=$newPasswordNotMatch}*{/section}
<label>{"Retype password"|i18n("design/standard/user")}</label><div class="labelbreak"></div>
<input class="halfbox" type="password" name="confirmPassword" size="11" value="{$confirmPassword|wash}" />
</div>
<div class="break"></div>
</div>

<div class="buttonblock">
<input class="defaultbutton" type="submit" name="OKButton" value="{'OK'|i18n('design/standard/user')}" />
<input class="button" type="submit" name="CancelButton" value="{'Cancel'|i18n('design/standard/user')}" />
</div>

</form>
