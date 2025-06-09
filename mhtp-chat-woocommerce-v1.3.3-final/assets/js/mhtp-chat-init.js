(function(){
    function getParam(name){
        return new URLSearchParams(window.location.search).get(name);
    }
    function getExpertId(){
        if(window.mhtpChatData && parseInt(window.mhtpChatData.ExpertId,10)){
            return parseInt(window.mhtpChatData.ExpertId,10);
        }
        var fromUrl = getParam('ExpertId');
        if(fromUrl){
            return parseInt(fromUrl,10);
        }
        console.warn('ExpertId missing. Falling back to default 392');
        return 392;
    }
    function init(){
        var expertId = getExpertId();
        if(!window.Typebot || typeof window.Typebot.initStandard !== 'function'){
            console.error('Typebot library not loaded');
            return;
        }
        try {
            window.Typebot.initStandard({
                variables:{ExpertId: expertId}
            });
        } catch(e){
            console.error('Typebot initialization failed', e);
        }
    }
    if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
