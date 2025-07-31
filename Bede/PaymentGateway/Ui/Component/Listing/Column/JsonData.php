<?php

namespace Bede\PaymentGateway\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class JsonData extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[$fieldName])) {
                    $jsonData = $item[$fieldName];

                    if (empty($jsonData)) {
                        $item[$fieldName] = '<span style="color: #999; font-style: italic;">No data</span>';
                        continue;
                    }

                    // Try to decode JSON
                    $decoded = json_decode($jsonData, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        // Format as collapsible/expandable content
                        $preview = $this->getJsonPreview($decoded);
                        $fullJson = htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8');

                        $item[$fieldName] = '
                            <div class="json-data-container">
                                <div class="json-preview" style="max-width: 200px; font-size: 11px; color: #666;">
                                    ' . $preview . '
                                </div>
                                <button type="button" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === \'none\' ? \'block\' : \'none\'; this.textContent = this.textContent === \'Show\' ? \'Hide\' : \'Show\';" style="font-size: 10px; padding: 2px 6px; margin-top: 3px; background: #007cba; color: white; border: none; border-radius: 3px; cursor: pointer;">Show</button>
                                <div style="display: none; max-width: 400px; max-height: 200px; overflow: auto; background: #f9f9f9; padding: 8px; border: 1px solid #ddd; margin-top: 5px; font-family: monospace; font-size: 10px;">
                                    <pre>' . $fullJson . '</pre>
                                </div>
                            </div>';
                    } else {
                        // Not valid JSON, show truncated text
                        $truncated = strlen($jsonData) > 100 ? substr($jsonData, 0, 100) . '...' : $jsonData;
                        $item[$fieldName] = '<div style="max-width: 200px; font-size: 11px; word-break: break-all;">' .
                            htmlspecialchars($truncated) . '</div>';
                    }
                }
            }
        }

        return $dataSource;
    }

    private function getJsonPreview($data)
    {
        if (is_array($data)) {
            $count = count($data);
            $keys = array_keys($data);
            $preview = "Array ($count items)";

            if ($count > 0) {
                $firstKeys = array_slice($keys, 0, 3);
                $keyStr = implode(', ', $firstKeys);
                if ($count > 3) {
                    $keyStr .= '...';
                }
                $preview .= "<br><small>Keys: $keyStr</small>";
            }

            return $preview;
        }

        return 'Object';
    }
}
