This {'is'} a template function test!

{* Using native arguments *}
{if is_set( $arguments[0] )}
  With argument[0] being: "{$arguments[0]|wash}".
{/if}

{* Using post parameters ( Should not be used if method is GET as forced in Y.ez() or $.ez() ) *}
{if ezhttp_hasvariable( 'customPostParam', 'post' )}
  With customPostParam post parameter being: "{ezhttp( 'customPostParam', 'post' )|wash}". 
{/if}

