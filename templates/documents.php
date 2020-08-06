<script nonce="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()) ?>">
	var officeonline_permissions = '<?php p($_['permissions']) ?>';
	var officeonline_title = '<?php p($_['title']) ?>';
	var officeonline_fileId = '<?php p($_['fileId']) ?>';
	var officeonline_token = '<?php p($_['token'] ? $_['token'] : "''") ?>';
	var officeonline_urlsrc = '<?php p($_['urlsrc'] ? $_['urlsrc'] : "''") ?>';
	var officeonline_path = '<?php p($_['path']) ?>';
	var officeonline_userId = <?php isset($_['userId']) ? print_unescaped('\'' . \OCP\Util::sanitizeHTML($_['userId']) . '\'') : print_unescaped('null') ?>;
	var officeonline_instanceId = '<?php p($_['instanceId']) ?>';
	var officeonline_canonical_webroot = '<?php p($_['canonical_webroot']) ?>';
	var officeonline_directEdit = <?php isset($_['direct']) ? p('true') : p('false') ?>;
</script>

<?php
script('officeonline', 'document');
?>
<div id="loadingContainer"></div>
<div id="proxyLoadingContainer">
	<div id="proxyLoadingIcon"></div>
	<div id="proxyLoadingMessage"></div>
</div>
<div id="documents-content"></div>
