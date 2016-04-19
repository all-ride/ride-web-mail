{extends file="base/index"}

{block name="content_title"}
    <div class="page-header">
        <h1>{translate key="title.mail"}</h1>
    </div>
{/block}

{block name="content" append}
    {include file="base/form.prototype"}

    <form id="{$form->getId()}" class="form form--selectize" action="{$app.url.request}" method="POST" role="form">
        <div class="form__group">

            <div class="spacer">
                <div class="grid">
                    <div class="grid__12 grid--bp-med__3">
                        {call formRow form=$form row="from_name"}
                    </div>
                    <div class="grid__12 grid--bp-med__9">
                        {call formRow form=$form row="from"}
                    </div>
                </div>
            </div>

            <div class="spacer">
                <div class="grid">
                    <div class="grid__item">
                        {call formRow form=$form row="subject"}
                    </div>
                </div>
            </div>

            <div class="spacer">
                <div class="grid">
                    <div class="grid__item">
                        {call formRow form=$form row="emails"}
                    </div>
                </div>
            </div>

            <div class="spacer">
                <div class="grid">
                    <div class="grid__12 grid--bp-med__8">
                        {call formRow form=$form row="body"}
                    </div>

                    <div class="grid__12 grid--bp-med__4 variables">
                        <p>{translate key="label.mail.body.description"}</p>

                        <select class="selectize variable-select">
                            {foreach $variables as $model=>$modelVariables}
                                <option value="{$model}">
                                    {$model}
                                </option>
                            {/foreach}
                        </select>

                        <div class="variables">
                            {foreach $variables as $model=>$modelVariables}
                                <div id="variables-{$model}" class="modelVariables" style="display:none;">
                                    {foreach $modelVariables as $label=>$variable}
                                        <a href="#" class="btn btn--small btn--brand variable" style="margin-bottom:5px;" data-variable="[[{$variable}]]">
                                            {translate key=$label}
                                        </a>
                                    {/foreach}
                                </div>
                            {/foreach}
                        </div>

                    </div>
                </div>
            </div>

            {call formRows form=$form}
            <div class="form__actions">
                <input type="submit" class="btn btn--primary" value="{translate key="button.send"}" />
                {if $redirect}
                    <a class="btn btn--link" href="{$redirect}">{translate key="button.cancel"}</a>
                {/if}
            </div>
        </div>
    </form>

    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            var $ = window.jQuery,
                $mail = $('#form-body');

            $('.variable').click(function(e) {
                e.preventDefault();

                $mail.redactor('insert.text', $(this).data('variable'));
                $(this).blur();
            });

            $('.variable-select').on('change', function() {
                $('.modelVariables').hide();
                $('#variables-' + this.value).show();
                $mail.focus();
            });

            $('#variables-' + $('.variable-select').val()).show();
        });
    </script>
{/block}


