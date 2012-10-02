## SURFconext
If you want to use SURFconext as an IdP the configuration needs to be like the 
snippet below:

    $metadata['https://engine.surfconext.nl/authentication/idp/metadata'] = array(
        'SingleSignOnService' => 'https://engine.surfconext.nl/authentication/idp/single-sign-on',
        'certFingerprint' => 'a36aac83b9a552b3dc724bfc0d7bba6283af5f8e',

        // clean up the attributes received from the IdP and modify them to use
        // our naming convention
        'authproc' => array(
            50 => array(
                'class' => 'core:AttributeMap',
                'urn2name',
            ),
            51 => array(
                'class' => 'core:AttributeLimit',
                'cn', 'eduPersonEntitlement',
            ),
            52 => array(
                'class' => 'core:AttributeMap',
                'eduPersonEntitlement' => 'entitlement',
                'cn' => 'displayName',
            ),
        ),

    );

You will need to request that your service is provided with the 
`urn:mace:dir:attribute-def:cn` and 
`urn:mace:dir:attribute-def:eduPersonEntitlement` attributes. The `authproc` 
rule `50` strips of the `urn:mace:dir:attribute-def` part from the attribute,
the rule `51` removes all attributes except `cn` and `eduPersonEntitlement` and
finally rule `52` renames the attribute `eduPersonEntitlement` to `entitlement`
and `cn` to `displayName`. 

`php-oauth` will use a persistent Name ID as a unique identifier for a user, so
no attribute is used for that. If you want for instance to have more information
about the user, you can also request some other attributes and configure/map 
them here.
