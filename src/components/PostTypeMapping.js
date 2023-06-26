import Toggle from "react-toggle";
import { useEffect, useState } from "react";
import axios from "axios";
import { fetchToken } from "./utils";
import { Oval } from "react-loader-spinner";

const PostTypeMapping = ({
  token,
  tokenData,
  setMappedFields,
  mappedFields,
  selectedPostType,
  setSelectedPostType
}) => {
  const [enableMapping, setEnableMapping] = useState(false);
  const [registeredPostTypes, setRegisteredPostTypes] = useState({});
  const [postMetaKeys, setPostMetaKeys] = useState(null);
  const [fetchedTokenData, setFetchedTokenData] = useState(null);
  const [loadingFields, setLoadingFields] = useState(false);
  const getRegisteredPostFields = async () => {
    var params = new URLSearchParams();
    params.append("action", "aeropageGetRegisteredPostTypes");

    await axios
      .post(MYSCRIPT.ajaxUrl, params)
      .then(response => {
        if(response?.data?.status === "success"){
          setRegisteredPostTypes(response?.data?.post_types)
        }
      })
  }
  const getPostMetaForSelectedPostType = async (postType) => {
    var params = new URLSearchParams();
    params.append("action", "aeropageGetPostMetaForSelectedPostType");
    params.append("post_type", postType)
    await axios
      .post(MYSCRIPT.ajaxUrl, params)
      .then(response => {
        if(response?.data?.status === "success"){
          setPostMetaKeys(response?.data?.meta_keys);
        }
      })
  }
  const mapField = (metaField, airtableField, metaValues) => {
    const temp = {...mappedFields};

    temp[metaField] = {
      airtableField,
      metaValues: metaValues
    };
    setMappedFields(temp);
  }

  useEffect(() => {
    console.log(mappedFields);
  }, [mappedFields]);

  useEffect(() => {
    console.log("124", { selectedPostType });
    if(!selectedPostType) return;

    (async () => {
      setEnableMapping(true);

      setLoadingFields(true);
      await fetchToken(token).then(res => setFetchedTokenData(res));
      await getRegisteredPostFields();
      await getPostMetaForSelectedPostType(selectedPostType);
      setLoadingFields(false);
    })()
  }, [selectedPostType])

  return <div>
    <div className="div-wrapper">
      <div style={{
        display: "flex",
        flexDirection: "column",
        gap: "6px",
        marginTop: "10px"
      }}>
        <label>
          <Toggle
            defaultChecked={enableMapping}
            icons={false}
            checked={enableMapping}
            onChange={(e) => {
              setEnableMapping(!enableMapping);
              
              //If enableMapping is true.
              if(!enableMapping){
                getRegisteredPostFields();
              }
              //Retrieve the post types and the airtable fields from the token
            }} />
          <span className="label-text">Map to a Existing Post Type</span>
        </label>
        { 
          Object.keys(registeredPostTypes).length > 0 && 
          (
            <>
              <select
                value={selectedPostType}
                onChange={(e) => {
                  if(e.target.value === ""){
                    setSelectedPostType("");
                    setPostMetaKeys(null);
                    return;
                  };

                  setSelectedPostType(e.target.value);
                  //Get the registered post meta for each type
                  getPostMetaForSelectedPostType(e.target.value);
                  //get the token data to retrieve the fields
                  if(!tokenData && !fetchedTokenData){
                    fetchToken(token)
                      .then(res => setFetchedTokenData(res));
                  }
                }}
                style={{
                  height: "32px",
                  borderRadius: "6px",
                  backgroundColor: "white",
                  color: "#595B5C",
                  fontFamily: "'Inter', sans-serif",
                  fontStyle: "normal",
                  fontWeight: "400",
                  width: "75%",
                  border: "1px solid lightGray",
                  fontSize: "12px",
                  lineHeight: "18px",
                  marginTop: "6px"
                }}
              >
                <option
                  style={{
                    borderRadius: "6px",
                    color: "#595B5C",
                    fontFamily: "'Inter', sans-serif",
                    fontStyle: "normal",
                    fontWeight: "400",
                    fontSize: "12px",
                    lineHeight: "150%",
                  }}
                  value=""
                >
                  -- Post Type -- 
                </option>
                {
                  Object.keys(registeredPostTypes)
                    ?.sort((a, b) => {
                      let aLabel = registeredPostTypes[a]?.label?.toLowerCase();
                      let bLabel = registeredPostTypes[b]?.label?.toLowerCase();
                      if( aLabel > bLabel ){
                        return 1;
                      }

                      if(aLabel < bLabel){
                        return -1;
                      }

                      return 0;
                    })
                    ?.map(postType => (
                    <option
                      style={{
                        borderRadius: "6px",
                        color: "#595B5C",
                        fontFamily: "'Inter', sans-serif",
                        fontStyle: "normal",
                        fontWeight: "400",
                        fontSize: "12px",
                        lineHeight: "150%",
                      }}
                      value={postType}
                    >
                      { registeredPostTypes[postType]?.label }
                    </option>
                  ))
                }
              </select>
            </>
          )
        }
      </div>
    </div>
    <div className="div-wrapper">
    {
        !loadingFields && postMetaKeys && (
          <div style={{ 
            width: "75%"
          }}>
            { Object.keys(postMetaKeys)?.map(key => (
              <div className="div-wrapper" style={{
                display: "flex",
                flexDirection: "column",
                gap: "10px"
              }}>
                <div style={{
                  fontWeight: "bolder"
                }}>{key}</div>

                { Object.keys(postMetaKeys[key]).map(metaKey => {
                  if(metaKey === "_aero_cpt" || metaKey === "_aero_id") return;

                  return (
                    <div 
                      style={{
                        display: "flex",
                        flexDirection: "row",
                        justifyContent: "space-between"
                      }}
                    >
                      <span>Map {postMetaKeys[key][metaKey]?.label ?? metaKey} to &nbsp;</span>
                      <select
                        style={{
                          width: "150px"
                        }}
                        defaultValue={getFieldValue(mappedFields?.[metaKey]?.["airtableField"], fetchedTokenData )}
                        onChange={(e) => {
                          mapField(metaKey, e.target.value, postMetaKeys[key][metaKey]);
                        }}
                      >
                        <option value="">--</option>
                        { fetchedTokenData?.fields?.map(field => (<option value={field?.name}>{field?.name}</option>)) }
                      </select>
                    </div>
                  )
                }) }
                { Object.keys(postMetaKeys[key]).length <= 0 && (<>
                  <span>No meta keys or fields found.</span>
                </>) }
              </div>
              
            )) }
          </div>
        )
      }
      {
        loadingFields && (
          <>
            <div style={{ display: "flex", flexDirection: "row" }}>
              <Oval
                height={15}
                width={15}
                color="#4fa94d"
                wrapperStyle={{}}
                wrapperClass=""
                visible={true}
                ariaLabel="oval-loading"
                secondaryColor="#4fa94d"
                strokeWidth={2}
                strokeWidthSecondary={2}
              />
              <span
                style={{
                  color: "#595B5C",
                  fontFamily: "'Inter', sans-serif",
                  fontStyle: "normal",
                  fontWeight: "400",
                  marginTop: "2.5px",
                  fontSize: "10px",
                  lineHeight: "175%",
                }}
              >
                Loading Fields...
              </span>
            </div>
          </>
        )
      }
    </div>
  </div>;
};

const getFieldValue = (airtableField, tokenData) => {
  if(!tokenData) return "";
  if(!airtableField) return "";

  const field = tokenData?.fields?.find(field => field?.name === airtableField);
  console.log({ airtableField, name: field?.name })
  return field?.name;
}

export default PostTypeMapping;