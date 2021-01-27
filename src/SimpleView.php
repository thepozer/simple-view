<?php
/*
Copyright (c) 2014 Didier Prolhac <dev@thepozer.net>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

namespace Thepozer\View ;

use Psr\Http\Message\ResponseInterface;

class SimpleView implements \ArrayAccess, \Countable, \IteratorAggregate {
    private $arValues = array();

    private $sViewDir      = null;
    private $sViewSuffix   = null;
    private $sViewTemplate = null;

    private $sViewFilename = null;

    private $sCurrentView = null;

    public function __construct (string $sViewDir = 'views/', string $sViewSuffix = '.phtml', string $sViewTemplate = '_template.phtml') {
        $this->sViewDir      = $sViewDir;
        $this->sViewSuffix   = $sViewSuffix;
        $this->sViewTemplate = $sViewTemplate;
    }

    /**
     * Output rendered template
     *
     * @param ResponseInterface $response
     * @param  string $template Template pathname relative to templates directory
     * @param  array $data Associative array of template variables
     * @return ResponseInterface
     */
    public function render(ResponseInterface $oResponse, string $sView, array $arData = []): ResponseInterface {
        $oResponse->getBody()->write($this->fetch($sView, $arData));
        return $oResponse;
    }

    private function isCurrentView($mViewsNames): boolean {
        $arViewsNames = (is_array($mViewsNames)) ? $mViewsNames : [$mViewsNames];

        return in_array($this->sCurrentView, $arViewsNames);
    }

    private function fetch(string $sView, array $arData) : string {
        $arConfig = array('useTemplate' => true);
        if (array_key_exists('_conf', $arData)) {
            $arConfig = array_merge($arConfig, $arData['_conf']);
            unset($arData['_conf']);
        }

        $this->sCurrentView = $sView;
        $this->arValues = array_merge($this->arValues, $arData);

        if ($arConfig['useTemplate']) {
            $sTemplateView = $this->sViewDir . $this->sViewTemplate;
            $this->sViewFilename = $this->sViewDir . $sView . $this->sViewSuffix;
        } else {
            $this->sViewFilename = $sTemplateView = $this->sViewDir . $sView . $this->sViewSuffix;
        }

        if (file_exists($sTemplateView) && file_exists($this->sViewFilename)) {
            ob_start();
            require $sTemplateView;
            return ob_get_clean();
        }

        return '<h1>ERROR : View not found</h1>';
    }

    private function includeView() {
        require $this->sViewFilename;
    }

    /********************************************************************************
     * Magic functions
     *******************************************************************************/

    public function __set(string $sName, $mValue) {
        $this->offsetSet($sName, $mValue);
    }

    public function __get(string $sName) {
        return $this->offsetGet($sName);
    }

    public function __isset(string $sName) {
        return $this->offsetExists($sName);
    }

    public function __unset(string $sName) {
        $this->offsetUnset($sName);
    }

    /********************************************************************************
     * ArrayAccess interface
     *******************************************************************************/

    /**
     * Does this collection have a given key?
     *
     * @param string $sName The data key
     *
     * @return bool
     */
    public function offsetExists($sName) {
        return array_key_exists($sName, $this->arValues);
    }

    /**
     * Get collection item for key
     *
     * @param string $sName The data key
     *
     * @return mixed The key's value, or the default value
     */
    public function offsetGet($sName) {
        if (array_key_exists($sName, $this->arValues)) {
            return $this->arValues[$sName];
        } else {
            throw new \Exception("Undefined property : {$sName}");
        }
    }

    /**
     * Set collection item
     *
     * @param string $sName  The data key
     * @param mixed  $mValue The data value
     */
    public function offsetSet($sName, $mValue) {
        $this->arValues[$sName] = $mValue;
    }

    /**
     * Remove item from collection
     *
     * @param string $sName The data key
     */
    public function offsetUnset($sName) {
        if (array_key_exists($sName, $this->arValues)) {
            unset($this->arValues[$sName]);
        }
    }
    
    /********************************************************************************
     * Countable interface
     *******************************************************************************/
    /**
     * Get number of items in collection
     *
     * @return int
     */
    public function count(): int {
        return count($this->arValues);
    }

    /********************************************************************************
     * IteratorAggregate interface
     *******************************************************************************/
    /**
     * Get collection iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator {
        return new \ArrayIterator($this->arValues);
    }
 
}
