package com.example.receipttracker;

import androidx.appcompat.app.AppCompatActivity;

import android.content.Intent;
import android.os.AsyncTask;
import android.os.Bundle;
import android.util.Log;
import android.widget.ArrayAdapter;
import android.widget.AutoCompleteTextView;
import android.widget.TextView;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.io.OutputStreamWriter;
import java.lang.ref.WeakReference;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.Dictionary;
import java.util.Enumeration;
import java.util.Hashtable;
import java.util.List;
import java.util.Map;
import java.util.TreeMap;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import org.w3c.dom.Text;

import javax.net.ssl.HttpsURLConnection;

public class MainActivity extends AppCompatActivity {



    //Dictionary holding countries and countryIDs from the Back4App api
    Map<String,String> _dictCountries;

    //testing reading rest API
    TextView tv;// = (TextView)findViewById(R.id.testTextView);

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        AutoCompleteTextView _ddlCountries = (AutoCompleteTextView) findViewById(R.id._autoCompCountries);
        _ddlCountries.setThreshold(1);
        InitializeCDictionaries();

        //Find the countries and save it within the dictionary
        FindCountries();

        try {
            Thread.sleep(5000);
        }
        catch (InterruptedException e) {
            e.printStackTrace();
        }

        //Log.d("MainActivity: ", "I'm in main first");
        //testing drop down list filling
        List<String> myCountries = new ArrayList<String>(_dictCountries.keySet());
        ArrayAdapter<String> adapter = new ArrayAdapter<String>(this,
                android.R.layout.simple_dropdown_item_1line, myCountries);
        _ddlCountries.setAdapter(adapter);

        //testing the User was parceled and passed correctly to this intent.
        //Intent which came from the login page.
        Intent userLoggedInIntent = getIntent();

        //User which was passed with the intent.
        User loggedInUser = userLoggedInIntent.getParcelableExtra("Logged In User");
        //TextView tv = (TextView)findViewById(R.id.testTextView);
        tv = (TextView)findViewById(R.id.testTextView);
        tv.setText(loggedInUser.getUserID() + " " + loggedInUser.getToken());

        ReadRESTAPITest readRESTAPITest = new ReadRESTAPITest(this);

        readRESTAPITest.execute();
    }

    private void InitializeCDictionaries()
    {
        _dictCountries = new Hashtable<>();
    }

    private void FindCountries()
    {
        //FindCountriesAsync findCountriesAsync = new FindCountriesAsync(this);
        //findCountriesAsync.execute();

        //testing Back4App api to get all countries
        (new Thread(new Runnable() {
            @Override
            public void run() {
                try {
                    URL url = new URL("https://parseapi.back4app.com/classes/Continentscountriescities_Country?count=1&limit=250&order=name&keys=name");
                    HttpURLConnection urlConnection = (HttpURLConnection)url.openConnection();
                    urlConnection.setRequestProperty("X-Parse-Application-Id", "Ba2uRdWlbvbzwellUA7gTmmDEZf6bRPrXpnd8VaO"); // This is your app's application id
                    urlConnection.setRequestProperty("X-Parse-REST-API-Key", "jxCa0OM9D0yd1S89ysT1Fjo2tkfJxorvFg3L36RC"); // This is your app's REST API key
                    try {
                        BufferedReader reader = new BufferedReader(new InputStreamReader(urlConnection.getInputStream()));
                        StringBuilder stringBuilder = new StringBuilder();
                        String line;
                        while ((line = reader.readLine()) != null) {
                            stringBuilder.append(line);
                        }
                        //Parse the data from the API using JSON
                        JSONObject data = new JSONObject(stringBuilder.toString()); // Here you have the data that you need
                        JSONArray results =  data.getJSONArray("results");
                        //Log.d("Objects in data", data.names().toString(2));

                        for(Integer i = 0; i < results.length(); ++i)
                        {
                            //Log.d("Reading JSON Results", results.getJSONObject(i).get("name").toString());
                            _dictCountries.put(results.getJSONObject(i).get("name").toString(), results.getJSONObject(i).get("objectId").toString());
                        }

                        //sort the dictionary
                        _dictCountries = new TreeMap<>(_dictCountries);
                    }
                    finally {
                        urlConnection.disconnect();

                    }
                } catch (Exception e) {
                    Log.e("Something went wrong", e.toString());
                }
            }
        })).start();
    }

    private static class ReadRESTAPITest extends AsyncTask<Void, Void, JSONObject>
    {
        //used this as a guide https://www.journaldev.com/12607/android-login-registration-php-mysql#android-login-registration-app
        //https://camposha.info/android-php-mysql-save-http-post-httpurlconnection/
        //https://stackoverflow.com/questions/9767952/how-to-add-parameters-to-httpurlconnection-using-post-using-namevaluepair
        //https://stackoverflow.com/questions/4205980/java-sending-http-parameters-via-post-method-easily
        //https://stackoverflow.com/questions/40574892/how-to-send-post-request-with-x-www-form-urlencoded-body
        //https://prodevsblog.com/view/android-httpurlconnection-post-and-get-request-tutorial/

        //JSON object to be passed to the other pages
        //private JSONObject returnedObject;
        //Weak reference to the login page
        private WeakReference<MainActivity> mainActivityWeakReference;

        public ReadRESTAPITest(MainActivity mainActivity)
        {
            //Assign the weak reference variable to the login page.
            mainActivityWeakReference = new WeakReference<MainActivity>(mainActivity);
        }

        @Override
        protected void onPreExecute() {
            super.onPreExecute();

            //check that the login page is null or finishing
            MainActivity loginPage = mainActivityWeakReference.get();
            if(loginPage == null || loginPage.isFinishing())
                return;

            loginPage.tv.setText("Trying to read from REST API");
        }



        @Override
        protected JSONObject doInBackground(Void... strings)
        {
            //JSONobject which will be filled with the data from the server.
            JSONObject data = new JSONObject();

            try {
                //create the parameters string to log in
                //String urlParameters = "submit=Login&" + "email=" + strings[0] + "&password=" + strings[1];
                String urlParameters = "message=Iamworking}";
                byte[] postData = urlParameters.getBytes(StandardCharsets.UTF_8);

                //URL and HTTP POST connection to the server.
                URL url = new URL("http://www.mokarrom.com/ReceiptWebservice/svc/transactions");
                HttpURLConnection urlConnection = (HttpURLConnection)url.openConnection();
                urlConnection.setRequestMethod("POST");
                urlConnection.setReadTimeout(20000);
                urlConnection.setConnectTimeout(20000);
                urlConnection.setDoInput(true);
                urlConnection.setDoOutput(true);
                urlConnection.setRequestProperty( "Content-Type", "application/x-www-form-urlencoded");
                urlConnection.setRequestProperty( "charset", "utf-8");
                urlConnection.setRequestProperty( "Content-Length", Integer.toString( postData.length ));
                urlConnection.setUseCaches( false );

                //Try to send the data and read the response
                try {
                    //Send the data with an output stream
                   OutputStream os = urlConnection.getOutputStream();
                    BufferedWriter bw = new BufferedWriter(new OutputStreamWriter(os, "UTF-8"));
                    bw.write(urlParameters);
                    //flush the writer and close it and the output stream
                    bw.flush();
                    bw.close();
                    os.close();

                    if(urlConnection.getResponseCode() == HttpsURLConnection.HTTP_OK) {
                        BufferedReader reader = new BufferedReader(new InputStreamReader(urlConnection.getInputStream()));
                        StringBuilder stringBuilder = new StringBuilder();
                        String line;
                        while ((line = reader.readLine()) != null) {
                            stringBuilder.append(line);
                        }
                        //Parse the data from the API using JSON
                        data = new JSONObject(stringBuilder.toString()); // Here you have the data that you need
                        //String myToken = data.getString("token");
                        Log.d("Token found = ", "myToken");

                        //Assign the found jason object the class JSONObject
                        //returnedObject = data;
                    }
                }
                finally {
                    urlConnection.disconnect();
                    return data;
                }
            }
            catch (Exception e) {
                Log.e("Something went wrong", e.toString());
            }

            return null;
        }

        @Override
        protected void onPostExecute(JSONObject jsonObject) {
            super.onPostExecute(jsonObject);

            try
            {
                //check that the login page is null or finishing
                MainActivity loginPage = mainActivityWeakReference.get();

                if(loginPage == null || loginPage.isFinishing())
                    return;

                if(jsonObject.getBoolean("found")) {

                    if(jsonObject.getBoolean("correctPass")) {
                        loginPage.tv.setText("You have been logged in.");

                        //Go to main menu landing page of the App with a new User object
                        //which is created using the JSONObject.
                        User loggedInUser = new User(jsonObject);
                        Intent mainLandingPageIntent = new Intent(loginPage.getApplicationContext(), MainActivity.class);
                        //When sending this way, the intent is null in the next page.
                        //This was fixed by placing the intent in the onCreate method of the next activity.
                        mainLandingPageIntent.putExtra("Logged In User", loggedInUser);

                        //Start the next activity
                        loginPage.startActivity(mainLandingPageIntent);
                        //loginPage.startActivity(new Intent(loginPage.getApplicationContext(), MainActivity.class));
                    }
                    else
                    {
                        loginPage.tv.setText("You have entered and incorrect password. Please try again.");
                    }
                }
                else
                {
                    loginPage.tv.setText("There does not seem to be any users associated with this email. Please click the Sign Up button to register your account.");
                    //Log.d("User not found", "not found");
                }
            } catch (JSONException e) {
                e.printStackTrace();
            }
        }
    }
}
