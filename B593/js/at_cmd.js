/**
 * Created by Jari on 9.3.2015.
 */
require(["dojo/ready", "dojo/on", "dojo/dom", "dojo/dom-style", "dojo/query", "dojo/_base/array"],
    function (ready, on, dom, domStyle, query, array)
{
    var output_text_field;
    var explanation_div;
    var explain_btn;

    ready(function ()
    {
        output_text_field = dom.byId("output_text");
        explanation_div = dom.byId("explanation");
        explain_btn = dom.byId("explain_btn");

        on(explain_btn, "click", function (event)
        {
            var output = output_text_field.value;
            ExplainOutput(output);
        });
    });

    function ExplainOutput(output)
    {
        var xhrArgs = {
            postData: {json: dojo.toJson({output: output})},
            handleAs: "json",
            url: "?op=explain",
            load: function (data)
            {
                if (data.error) {
                    explanation_div.innerHTML = data.error;
                    alert('Failed!');
                } else {
                    explanation_div.innerHTML = data.explanation;
                }
            },
            error: function (error)
            {
                alert("Failed! " + error);
            }
        }

        // Call the asynchronous xhrPost
        var deferred = dojo.xhrPost(xhrArgs);
    };

});