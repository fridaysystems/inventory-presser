(()=>{"use strict";const e=window.wp.blocks,t=window.wp.i18n,o=window.wp.components,n=window.wp.coreData,i=window.wp.blockEditor,s=window.wp.element,p=window.ReactJSXRuntime,r=JSON.parse('{"UU":"inventory-presser/youtube"}');(0,e.getBlockType)(r.UU)||(0,e.registerBlockType)(r.UU,{edit:function({isSelected:e,context:r}){const{postType:d}=r,[c,l]=(0,n.useEntityProp)("postType",d,"meta"),u=(0,i.useBlockProps)(),w=c[invp_blocks.meta_prefix+"youtube"],a=c[invp_blocks.meta_prefix+"youtube_embed"];(0,s.useEffect)((()=>{""===w||11>w.length||wp.apiRequest({url:wp.media.view.settings.oEmbedProxyUrl,data:{url:"https://www.youtube.com/watch?v="+w},type:"GET",dataType:"json",context:this}).done(b).fail((e=>{console.log("YouTube API error",e.responseJSON)}))}),[w]);const b=e=>{l({...c,[invp_blocks.meta_prefix+"youtube_embed"]:e.html});const t=document.getElementById(u.id+"-oembed");t&&(t.innerHTML=e.html)};return(0,p.jsx)("div",{...u,children:e||""===a?(0,p.jsx)(o.TextControl,{label:(0,t.__)("YouTube Video ID","inventory-presser"),value:w,onChange:e=>l({...c,[invp_blocks.meta_prefix+"youtube"]:e,[invp_blocks.meta_prefix+"youtube_embed"]:""})}):(0,p.jsx)("div",{id:u.id+"-oembed",children:(m=a,wp.element.RawHTML({children:m}))})});var m}})})();