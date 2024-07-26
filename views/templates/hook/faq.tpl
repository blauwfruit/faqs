<div class="row mb-4">
    <div class="col-12">
        <h2>{l s='Frequently Asked Questions about %s' mod='faqs' sprintf=[$pageName]}</h2>
        <div class="faq">
            {foreach from=$faqs item=item key=key}
                <div class="faq-item">
                    <div class="faq-question faq-closed">
                        {$item.question}
                    </div>
                    <div class="faq-answer">
                        <div>{$item.answer}</div>
                        <div class="faq-feedback text-muted">
                            <div class="row">
                                <div class="col-lg-6">
                                    <span>{l s='Is this answer useful?' mod='faqs'}</span>
                                    <span class="thumb-up ml-2 ml-lg-0" data-id-faq-question="{$item.id_faq_question}">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 -960 960 960" width="24px" fill="#6c757d"><path d="M840-640q32 0 56 24t24 56v80q0 7-2 15t-4 15L794-168q-9 20-30 34t-44 14H280v-520l240-238q15-15 35.5-17.5T595-888q19 10 28 28t4 37l-45 183h258Zm-480 34v406h360l120-280v-80H480l54-220-174 174ZM160-120q-33 0-56.5-23.5T80-200v-360q0-33 23.5-56.5T160-640h120v80H160v360h120v80H160Zm200-80v-406 406Z"/></svg>
                                    </span>
                                    <span class="thumb-down ml-2 ml-lg-0" data-id-faq-question="{$item.id_faq_question}">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 -960 960 960" width="24px" fill="#6c757d"><path d="M120-320q-32 0-56-24t-24-56v-80q0-7 2-15t4-15l120-282q9-20 30-34t44-14h440v520L440-82q-15 15-35.5 17.5T365-72q-19-10-28-28t-4-37l45-183H120Zm480-34v-406H240L120-480v80h360l-54 220 174-174Zm200-486q33 0 56.5 23.5T880-760v360q0 33-23.5 56.5T800-320H680v-80h120v-360H680v-80h120Zm-200 80v406-406Z"/></svg>
                                    </span>
                                </div>
                                <div class="col-lg-6 mt-2 mt-lg-0">
                                {if $item.vote_count >= 1 && $item.usefulness_count != null}
                                    <small class="text-muted float-lg-right">
                                        {l s='%d out of %d found this answer usefull.' sprintf=[$item.usefulness_count, $item.vote_count] mod='faqs'}
                                    </small>
                                {/if}
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            {/foreach}
        </div>
    </div>
</div>
