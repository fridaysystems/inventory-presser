(()=>{"use strict";const e=window.wp.blocks,n=window.wp.i18n,i=window.wp.components,o=window.wp.coreData,t=(window.wp.data,window.wp.blockEditor),s=window.ReactJSXRuntime,p=JSON.parse('{"UU":"inventory-presser/vin"}');(0,e.getBlockType)(p.UU)||(0,e.registerBlockType)(p.UU,{edit:function({isSelected:e,context:p}){const{postType:r}=p,[w,c]=(0,o.useEntityProp)("postType",r,"meta"),d=(0,t.useBlockProps)();return e?(0,s.jsx)("div",{...d,children:(0,s.jsx)(i.TextControl,{label:(0,n.__)("VIN","inventory-presser"),value:w[invp_blocks.meta_prefix+"vin"],onChange:e=>c({...w,[invp_blocks.meta_prefix+"vin"]:e})})}):(0,s.jsxs)("div",{...d,children:[" ",w[invp_blocks.meta_prefix+"vin"]," "]})}})})();