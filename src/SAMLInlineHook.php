<?php
/** Copyright © 2019-2020 Dragos Gaftoneanu
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
namespace Okta\Hooks;
use Exception;

class SAMLInlineHook extends Exception
{
	protected $request = "";
	protected $response = array();
	
	public function __construct()
	{
		$request = json_decode(file_get_contents('php://input'),true);
		
		if($request['eventType'] != "com.okta.saml.tokens.transform")
			return $this->error("Incorrect event type is selected in the request.");
		else
			$this->request = $request;
	}
	
	public function display()
	{
		return json_encode(array("commands" => array(array("type" => "com.okta.assertion.patch", "value" => $this->response))),JSON_UNESCAPED_SLASHES);
	}
	
	public function addClaim($name, $nameFormat, $xsiType, $value)
	{
		$this->response[] = array(
			"op" => "add",
			"path" => "/claims/" . $name,
			"value" => array(
				"attributes" => array(
					"NameFormat" => $nameFormat
				),
				"attributeValues" => array(array(
					"attributes" => array(
						"xsi:type" => $xsiType
					),
					"value" => $value
				))
			)
		);
	}
	
	public function modifyClaim($name, $newValue)
	{
		$this->response[] = array(
			"op" => "replace",
			"path" => "/claims/" . $name . "/attributeValues/value",
			"value" => $newValue
		);
	}
	
	public function modifyClaimArray($name, $position, $newValue)
	{
		$this->response[] = array(
			"op" => "replace",
			"path" => "/claims/" . $name . "/attributeValues/" . $position . "/value",
			"value" => $newValue
		);
	}
	
	public function modifyAssertion($path, $newValue)
	{
		$this->response[] = array(
			"op" => "replace",
			"path" => $path,
			"value" => $newValue
		);
	}
	
	public function getRaw()
	{
		return $this->request;
	}
	
	private function error($message)
	{		
		throw new \Exception(
			json_encode(array(
				"error" => array(
					"errorSummary" => $message
				)
			),JSON_UNESCAPED_SLASHES)
		);
	}
}
