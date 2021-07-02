<?php


namespace archive\coreapp;


abstract class ContentHandler
{
    public static function getContentFromRequest(): array
    {
        $contentFromRequest = array();
        $server = $_SERVER;

        $contentFromRequest['service']['http_host'] = $server['HTTP_HOST'];
        $contentFromRequest['service']['method'] = strtoupper($server['REQUEST_METHOD']);

        if ($contentFromRequest['service']['method'] == 'POST' || $contentFromRequest['service']['method'] == 'PUT' || $contentFromRequest['service']['method'] == 'DELETE') {
            if (file_get_contents('php://input')) {
                $jsonContent = file_get_contents('php://input');
                if (WORK_MODE_APP == 'dev') {
                    RequestLogger::saveRequestToLog($jsonContent);
                }
                $contentFromRequest['data'] = json_decode($jsonContent, true);
            } else {
                $response = json_encode([
                    'command' => 'Метод ' . $contentFromRequest['service']['method'],
                    'error' => '1',
                    'message' => 'Отсутствуют данные для сохранения',
                ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

                header('Content-Type: application/json');
                die($response);
            }
        } else {
            $contentFromRequest['data']['query_string'] = $server['QUERY_STRING'] ?? '';
        }

        $contentFromRequest['service']['uri'] = $server['REQUEST_URI'];
        if (strpbrk($server['REQUEST_URI'],'?')) {
            $arrayPathInfoUri = explode('?', $server['REQUEST_URI']);
            $contentFromRequest['service']['uri'] = $arrayPathInfoUri[0];
        }

        $contentFromRequest['service']['path_info'] = $contentFromRequest['service']['uri'];
        if (!empty($server['PATH_INFO'])) {
            $contentFromRequest['service']['path_info'] = $server['PATH_INFO'];
        }

        return $contentFromRequest;
    }

}
