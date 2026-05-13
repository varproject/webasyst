<?php

abstract class logsBackendAutocompleteController extends waController
{
    const RESULTS_LIMIT = 10;

    abstract protected function getResultsContents($cut_items);

    protected function getResults($items)
    {
        $cut_items = array_slice($items, 0, self::RESULTS_LIMIT, true);
        $result = $this->getResultsContents($cut_items);

        if (count($cut_items) < count($items)) {
            $result[] = [
                'value' => null,
                'label' => '<span class="gray">' . _w('To view other results, enter a more detailed query') . '</span>',
            ];
        }

        return $result;
    }

    protected function response($response = [])
    {
        echo json_encode($response);
        die;
    }
}
