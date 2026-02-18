<?php

namespace Drupal\edw_document\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\Url;
use Drupal\edw_document\Response\CacheableBinaryFileResponse;
use Drupal\edw_document\Services\DocumentManager;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\stage_file_proxy\DownloadManagerInterface;


/**
 * Controller for files.
 */
class FileController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The document manager service.
   *
   * @var \Drupal\edw_document\Services\DocumentManager
   */
  protected $documentManager;

  /**
   * The File storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * The path processor service.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Stage File Proxy download manager service.
   *
   * @var \Drupal\stage_file_proxy\DownloadManagerInterface
   */
  protected $downloadManager;


  /**
   * Constructs a new FileController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\edw_document\Services\DocumentManager $document_manager
   *   The document manager service.
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   The inbound path processor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *    The config factory service.
   * @param \Drupal\stage_file_proxy\DownloadManagerInterface $download_manager
   *    The Stage File Proxy download manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, DocumentManager $document_manager, InboundPathProcessorInterface $path_processor, ConfigFactoryInterface $config_factory, ?DownloadManagerInterface $download_manager = NULL) {
    $this->fileStorage = $entity_type_manager->getStorage('file');
    $this->documentManager = $document_manager;
    $this->pathProcessor = $path_processor;
    $this->configFactory = $config_factory;
    $this->downloadManager = $download_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $download_manager = $container->has('stage_file_proxy.download_manager')
      ? $container->get('stage_file_proxy.download_manager')
      : NULL;
    return new static(
      $container->get('entity_type.manager'),
      $container->get('edw_document.document.manager'),
      $container->get('path_processor_manager'),
      $container->get('config.factory'),
      $download_manager
    );
  }

  /**
   * Serves a file for download.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $uuid
   *   The UUID of the file to be served.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The response object for serving the file.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when no file with the provided UUID exists.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public function serveFile(Request $request, $uuid) {
    $url = $this->processPath($request);
    $uuid = $url->getRouteParameters()['uuid'];
    $files = $this->fileStorage->loadByProperties([
      'uuid' => $uuid,
    ]);
    /** @var \Drupal\file\Entity\File $file */
    $file = reset($files);
    if (!$file instanceof FileInterface) {
      return throw new NotFoundHttpException();
    }
    $uri = $file->getFileUri();
    if (!file_exists($uri)) {
      if (!$this->downloadManager || !str_starts_with($uri, 'public://')) {
        throw new NotFoundHttpException();
      }

      $config = $this->configFactory->get('stage_file_proxy.settings');
      $server = (string) $config->get('origin');
      if ($server === '') {
        throw new NotFoundHttpException();
      }
      $server = rtrim($server, "/ \t\n\r\0\x0B");

      $originDir = trim((string) ($config->get('origin_dir') ?? ''));
      $remoteFileDir = $originDir !== '' ? $originDir : $this->downloadManager->filePublicPath();

      $relativePath = StreamWrapperManager::getTarget($uri);

      $queryParameters = UrlHelper::filterQueryParameters($request->query->all());

      $options = [
        'verify' => $config->get('verify'),
        'query' => $queryParameters,
        'headers' => [],
      ];

      $this->downloadManager->fetch($server, $remoteFileDir, $relativePath, $options);

      if (!file_exists($uri)) {
        throw new NotFoundHttpException();
      }
    }
    $headers = [
      'Content-Type' => $file->getMimeType(),
      'Content-Length' => $file->getSize(),
    ];
    $filename = $request->query->get('filename') ?? $file->getFilename();
    $response = new CacheableBinaryFileResponse($uri, 200, $headers);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $filename);
    $response->addCacheableDependency($file);
    $response->getCacheableMetadata()->addCacheContexts(['url']);

    return $response;
  }

  /**
   * Processes the path for the given request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\Core\Url
   *   The processed URL object.
   */
  protected function processPath($request) {
    $path = $this->pathProcessor->processInbound($request->getPathInfo(), $request);
    return Url::fromUserInput($path, ['query' => $request->query->all()]);
  }

}
