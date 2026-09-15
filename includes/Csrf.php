<?php
/**
 * Per-session CSRF protection (Phase 7).
 *
 * Token placement convention (uniform for HTML forms and habblet/AJAX):
 * the token is submitted in the POST body field "csrf_token".
 * HTML forms emit Csrf::field(). Habblet/AJAX clients (Prototype Ajax.Request
 * and XMLHttpRequest) are hooked by Csrf::hookScript() to append the same field.
 */
final class Csrf
{
    public const FIELD = 'csrf_token';
    public const SESSION_KEY = '_csrf_token';
    private const TOKEN_BYTES = 32;

    public static function token(): string
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            return '';
        }
        $existing = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($existing) || $existing === '' || preg_match('/^[0-9a-f]{64}$/', $existing) !== 1) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(self::TOKEN_BYTES));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function submittedToken(): string
    {
        $value = $_POST[self::FIELD] ?? '';
        return is_string($value) ? $value : '';
    }

    public static function verify(?string $submitted = null): bool
    {
        $expected = $_SESSION[self::SESSION_KEY] ?? '';
        if (!is_string($expected) || $expected === '') {
            return false;
        }
        $given = $submitted === null ? self::submittedToken() : $submitted;
        if (!is_string($given) || $given === '') {
            return false;
        }
        return hash_equals($expected, $given);
    }

    public static function field(): string
    {
        $token = self::token();
        if ($token === '') {
            return '';
        }
        return '<input type="hidden" name="'.self::FIELD.'" value="'.htmlspecialchars($token, ENT_QUOTES, 'UTF-8').'">';
    }

    public static function isStateChangingRequest(): bool
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        return in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    public static function requireValid(): void
    {
        if (self::verify()) {
            return;
        }
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-store');
        exit('Request could not be completed.');
    }

    public static function protectPost(): void
    {
        if (self::isStateChangingRequest()) {
            self::requireValid();
        }
    }

    public static function boot(): void
    {
        self::token();
        if (!empty($GLOBALS['page']['csrf_skip'])) {
            return;
        }
        self::protectPost();
    }

    public static function hookScript(): string
    {
        $token = self::token();
        if ($token === '') {
            return '';
        }
        $jsonToken = json_encode($token, JSON_THROW_ON_ERROR);
        $jsonField = json_encode(self::FIELD, JSON_THROW_ON_ERROR);
        return '<script type="text/javascript">'
            .'window.PHPRetroCsrfToken='.$jsonToken.';'
            .'(function(){'
            .'var field='.$jsonField.';'
            .'var token=window.PHPRetroCsrfToken;'
            .'function addToken(params){'
            .'if(params==null||params===""){return field+"="+encodeURIComponent(token);}'
            .'if(typeof params==="string"){'
            .'if(params.indexOf(field+"=")!==-1){return params;}'
            .'return params+(params.length?"&":"")+field+"="+encodeURIComponent(token);'
            .'}'
            .'if(typeof params==="object"){try{params[field]=token;}catch(e){}return params;}'
            .'return params;'
            .'}'
            .'if(window.Ajax&&Ajax.Request&&Ajax.Request.prototype&&!Ajax.Request.prototype._phpretroCsrf){'
            .'Ajax.Request.prototype._phpretroCsrf=true;'
            .'var origRequest=Ajax.Request.prototype.request;'
            .'Ajax.Request.prototype.request=function(url){'
            .'var method=String(this.options.method||"post").toLowerCase();'
            .'if(method!=="get"){this.options.parameters=addToken(this.options.parameters);}'
            .'return origRequest.call(this,url);'
            .'};'
            .'}'
            .'if(window.XMLHttpRequest&&!XMLHttpRequest.prototype._phpretroCsrf){'
            .'XMLHttpRequest.prototype._phpretroCsrf=true;'
            .'var origOpen=XMLHttpRequest.prototype.open;'
            .'XMLHttpRequest.prototype.open=function(method){'
            .'this._phpretroMethod=String(method||"").toUpperCase();'
            .'return origOpen.apply(this,arguments);'
            .'};'
            .'var origSend=XMLHttpRequest.prototype.send;'
            .'XMLHttpRequest.prototype.send=function(body){'
            .'if(this._phpretroMethod&&this._phpretroMethod!=="GET"&&this._phpretroMethod!=="HEAD"){'
            .'if(typeof body==="string"||body==null||body===""){body=addToken(body);}'
            .'}'
            .'return origSend.call(this,body);'
            .'};'
            .'}'
            .'function injectForm(form){'
            .'if(!form||!form.tagName||String(form.tagName).toLowerCase()!=="form"){return;}'
            .'var method=String(form.method||"get").toLowerCase();'
            .'if(method!=="post"){return;}'
            .'if(form.getAttribute&&/^https?:\\/\\/www\\.paypal\\.com/i.test(String(form.getAttribute("action")||""))){return;}'
            .'if(form.querySelector&&form.querySelector("input[name=\\""+field+"\\"]")){return;}'
            .'var input=document.createElement("input");'
            .'input.type="hidden";input.name=field;input.value=token;'
            .'form.appendChild(input);'
            .'}'
            .'function onSubmit(event){'
            .'var form=event.target||event.srcElement;'
            .'injectForm(form);'
            .'}'
            .'if(document.addEventListener){document.addEventListener("submit",onSubmit,true);}'
            .'else if(document.attachEvent){document.attachEvent("onsubmit",onSubmit);}'
            .'function scan(){'
            .'var forms=document.getElementsByTagName("form");'
            .'for(var i=0;i<forms.length;i++){injectForm(forms[i]);}'
            .'}'
            .'if(document.addEventListener){document.addEventListener("DOMContentLoaded",scan,false);}'
            .'else if(window.attachEvent){window.attachEvent("onload",scan);}'
            .'if(document.readyState==="complete"){scan();}'
            .'})();'
            .'</script>';
    }
}
