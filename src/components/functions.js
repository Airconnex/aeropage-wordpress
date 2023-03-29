import axios from "axios";

export const processMedia = async ({
  responseData,
  setOpenMediaModal,
  setTotalMedia,
  isMediaCancelled,
  setCurrentMedia
}) => {
  console.log({responseData})
  if(responseData?.media){
    setOpenMediaModal(true);
    setTotalMedia(responseData?.media?.length);

    for(let i = 0; i < responseData?.media?.length; i += 1){
      //If the media modal is closed (cancelled), we will not proceed with the downloading
      if(isMediaCancelled.current > 0){
        isMediaCancelled.current = 0;
        break;
      }
      
      const media = responseData?.media[i];
      const index = i + 1;
      setCurrentMedia({ ...media, index: index });
      // console.log("LOOPING: ", media?.record_post_id, isMediaCancelled);
      
      let params = new URLSearchParams();
      params.append("action", "aeropageMediaDownload");  
      params.append("media", JSON.stringify(media));
      await axios.post(MYSCRIPT.ajaxUrl, params)
        .then(res => console.log(res))
        .catch(err => {
          alert(err?.response?.data?.message ?? err?.message);
        });
    }

    await sleep(3000);
    setOpenMediaModal(false);
    isMediaCancelled.current = 0;
  }
}

function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}
