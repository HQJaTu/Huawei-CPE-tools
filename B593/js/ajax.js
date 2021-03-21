/**
 * Created by Jari on 16.8.2014.
 * Update 31th Mar 2021
 */
require(["dojo/ready", "dojo/on", "dojo/dom", "dojo/dom-style", "dojo/query", "dojo/_base/array"],
    function (ready, on, dom, domStyle, query, array) {
        var encrypted_password_field;
        var decrypted_password_field;
        var encryption_radio;
        var target_radio;
        var key_field;
        var submit_btn;

        ready(function () {
            // This function won't run until the DOM has loaded and other modules that register
            // have run.
            encrypted_password_field = dom.byId("encrypted_password");
            decrypted_password_field = dom.byId("decrypted_password");
            encryption_radio = encrypted_password_field.form.encryption;
            target_radio = encrypted_password_field.form.target;
            key_field = dom.byId("key");
            submit_btn = dom.byId("submit_btn");

            domStyle.set(bottom_info, {position: "fixed", bottom: "15px", fontSize: "12px"});

            on(dom.byId("the_form"), "keyup", function (event) {
                if (event.keyCode == 13) {
                    // Stop the submit event since we want to control form submission.
                    dojo.stopEvent(event);

                    SubmitForm();
                }
            });

            on(encrypted_password_field, "keyup", function (event) {
                if (event.keyCode == 13) {
                    // Stop the submit event since we want to control form submission.
                    dojo.stopEvent(event);

                    const encrypted_value = GetRadioValue(encryption_radio);
                    const target_value = GetRadioValue(target_radio);
                    const encrypted_password = encrypted_password_field.value;
                    if (encrypted_value && target_value && encrypted_password.length > 0) {
                        DecryptPassword(encrypted_value, target_value, encrypted_password);
                    }
                } else {
                    decrypted_password_field.value = "";
                    key_field.value = "";

                    DisplaySubmit();
                }
            });

            on(encrypted_password_field, "change", function (event) {
                const encrypted_value = GetRadioValue(encryption_radio);
                const target_value = GetRadioValue(target_radio);
                const encrypted_password = encrypted_password_field.value;
                if (encrypted_value && target_value && encrypted_password.length > 0) {
                    DecryptPassword(encrypted_value, target_value, encrypted_password);
                }

                DisplaySubmit();
            });

            on(decrypted_password_field, "keyup", function (event) {
                encrypted_password_field.value = "";

                DisplaySubmit();
            });

            on(decrypted_password_field, "change", function (event) {
                const encrypted_value = GetRadioValue(encryption_radio);
                const target_value = GetRadioValue(target_radio);
                const decrypted_value = encrypted_password_field.value;
                const key = key_field.value;
                if (encrypted_value && target_value && encrypted_password.length > 0 && key.length > 0) {
                    EncryptPassword(encrypted_value, target_value, key, decrypted_value);
                }
            });

            on(submit_btn, "click", function (event) {
                SubmitForm();
            });

            DisplaySubmit();
        });

        function DisplaySubmit() {
            // Enable submit-button, if has input-value
            const encrypted_value = GetRadioValue(encryption_radio);
            const target_value = GetRadioValue(target_radio);
            const submit_btn = dom.byId("submit_btn");

            if (encrypted_value && target_value && (encrypted_password_field.value || decrypted_password_field.value)) {
                if (encrypted_password_field.value) {
                    submit_btn.value = "Decrypt";
                } else if (decrypted_password_field.value) {
                    submit_btn.value = "Encrypt";
                }
                domStyle.set(submit_btn, "display", "block");
            } else {
                domStyle.set(submit_btn, "display", "none");
            }
        }

        function SubmitForm() {
            const encrypted_value = GetRadioValue(encryption_radio);
            const target_value = GetRadioValue(target_radio);
            const encrypted_password = encrypted_password_field.value;
            const decrypted_value = decrypted_password_field.value;
            const key = key_field.value;
            if (encrypted_value && target_value && encrypted_password.length > 0) {
                DecryptPassword(encrypted_value, target_value, encrypted_password);
            } else if (encrypted_value && target_value && decrypted_value.length > 0 && key.length > 0) {
                EncryptPassword(encrypted_value, target_value, key, decrypted_value);
            }
        }

        function GetRadioValue(radio_field) {
            var radio_name;
            if (radio_field.length) {
                radio_name = radio_field[0].name;
            } else {
                radio_name = radio_field.name;
            }
            if (!radio_name) {
                return;
            }
            const checked_radios = query('#the_form input[type=radio]:checked');
            var radio_value;
            array.forEach(checked_radios, function (radio) {
                if (radio_name == radio.name) {
                    radio_value = radio.value;
                }
            });

            return radio_value;
        }

        function DecryptPassword(encryption, target, value) {
            const xhrArgs = {
                postData: {json: dojo.toJson({crypto: encryption, target: target, encrypted: value})},
                handleAs: "json",
                url: "?op=decrypt",
                load: function (data) {
                    const decrypted_password_field = dom.byId("decrypted_password");
                    const key_field = dom.byId("key");
                    key_field.value = data.key;
                    if (data.password == null) {
                        const old = domStyle.get(decrypted_password_field, "backgroundColor");
                        domStyle.set(decrypted_password_field, "backgroundColor", "red");
                    } else {
                        domStyle.set(decrypted_password_field, "backgroundColor", "#ffffff");
                        decrypted_password_field.value = data.password;
                    }
                },
                error: function (error) {
                    alert("Decrypt failed!");
                }
            }

            // Call the asynchronous xhrPost
            const deferred = dojo.xhrPost(xhrArgs);
        }

        function EncryptPassword(encryption, target, key, value) {
            const xhrArgs = {
                postData: {json: dojo.toJson({crypto: encryption, target: target, key: key, password: value})},
                handleAs: "json",
                url: "?op=encrypt",
                load: function (data) {
                    const encrypted_password_field = dom.byId("encrypted_password");
                    encrypted_password_field.value = data.ciphertext;
                },
                error: function (error) {
                    alert("Failed!");
                }
            }

            // Call the asynchronous xhrPost
            const deferred = dojo.xhrPost(xhrArgs);
        }

    });
