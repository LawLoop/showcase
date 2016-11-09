<?php 

return array(
    'includes' => array('_aws', '_sdk1'),
    'services' => array(
        'default_settings' => array(
            'params' => array(
                'key'    => get_cfg_var('aws.access_key') ? get_cfg_var('aws.access_key') : '',
                'secret' => get_cfg_var('aws.secret_key') ? get_cfg_var('aws.secret_key') : '',
                'region' => 'us-east-1', 
            )
        )
    )
);
?>

