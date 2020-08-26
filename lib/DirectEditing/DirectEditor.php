<?php

namespace OCA\Officeonline;

use OCA\Officeonline\AppInfo\Application;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\DirectEditing\IEditor;
use OCP\DirectEditing\IToken;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IURLGenerator;

class DirectEditor implements IEditor {
	public function __construct(IURLGenerator $urlGenerator,
								IL10N $l10n,
								ILogger $logger,
								AppConfig $config) {
		$this->urlGenerator = $urlGenerator;
		$this->trans = $l10n;
		$this->logger = $logger;
		$this->config = $config;
	}

	/**
	 * Return a unique identifier for the editor
	 *
	 * @return string
	 */
	public function getId(): string {
		return Application::APP_ID;
	}

	/**
	 * Return a readable name for the editor
	 *
	 * @return string
	 */
	public function getName(): string {
		return "Office Online";
	}

	/**
	 * A list of mimetypes that should open the editor by default
	 *
	 * @return array
	 */
	public function getMimetypes(): array {
		return Capabilities::MIMETYPES;
	}

	/**
	 * A list of mimetypes that can be opened in the editor optionally
	 *
	 * @return array
	 */
	public function getMimetypesOptional(): array {
		return Capabilities::MIMETYPES_OPTIONAL;
	}

	/**
	 * Return a list of file creation options to be presented to the user
	 *
	 * @return array of ACreateFromTemplate|ACreateEmpty
	 */
	public function getCreators(): array {
		return [
			new FileCreator($this->appName, $this->trans, $this->logger, "docx"),
			new FileCreator($this->appName, $this->trans, $this->logger, "xlsx"),
			new FileCreator($this->appName, $this->trans, $this->logger, "pptx")
		];
	}

	/**
	 * Return if the view is able to securely view a file without downloading it to the browser
	 *
	 * @return bool
	 */
	public function isSecure(): bool {
		return true;
	}

	/**
	 * Return a template response for displaying the editor
	 *
	 * open can only be called once when the client requests the editor with a one-time-use token
	 * For handling editing and later requests, editors need to implement their own token handling
	 * and take care of invalidation
	 *
	 * @param IToken $token - one time token
	 *
	 * @return Response
	 */
	public function open(IToken $token): Response {
		try {
			$token->useTokenScope();
			$file = $token->getFile();
			$fileId = $file->getId();
			$this->logger->debug("DirectEditor open: $fileId", ["app" => $this->appName]);

			$documentServerUrl = $this->config->GetDocumentServerUrl();

			if (empty($documentServerUrl)) {
				$this->logger->error("documentServerUrl is empty", ["app" => $this->appName]);
				return $this->renderError($this->trans->t("ONLYOFFICE app is not configured. Please contact admin"));
			}

			$userId = $token->getUser();
			$directToken = $this->crypt->GetHash([
				"userId" => $userId,
				"fileId" => $fileId,
				"action" => "direct",
				"iat" => time(),
				"exp" => time() + 30
			]);

			$filePath = $file->getPath();
			$filePath = preg_replace("/^\/" . $userId . "\/files/", "", $filePath);

			$params = [
				"documentServerUrl" => $documentServerUrl,
				"fileId" => null,
				"filePath" => $filePath,
				"shareToken" => null,
				"directToken" => $directToken,
				"inframe" => false
			];

			$response = new TemplateResponse($this->appName, "editor", $params, "base");

			$csp = new ContentSecurityPolicy();
			$csp->allowInlineScript(true);

			if (preg_match("/^https?:\/\//i", $documentServerUrl)) {
				$csp->addAllowedScriptDomain($documentServerUrl);
				$csp->addAllowedFrameDomain($documentServerUrl);
			} else {
				$csp->addAllowedFrameDomain($this->urlGenerator->getAbsoluteURL("/"));
			}
			$response->setContentSecurityPolicy($csp);

			return $response;
		} catch (\Exception $e) {
			$this->logger->error("DirectEditor open: " . $e->getMessage(), ["app" => $this->appName]);
			return $this->renderError($e->getMessage());
		}
	}

	/**
	 * Print error page
	 *
	 * @param string $error - error message
	 * @param string $hint - error hint
	 *
	 * @return TemplateResponse
	 */
	private function renderError($error, $hint = "") {
		return new TemplateResponse("", "error", [
			"errors" => [
				[
					"error" => $error,
					"hint" => $hint
				]
			]
		], "error");
	}
}
